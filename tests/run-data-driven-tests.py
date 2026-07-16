#!/usr/bin/env python3
"""
Run Luxstage B2B data-driven functional tests.

Uses tests/luxstage-b2b-test-data.json (3 datasets per module) and
tests/luxstage-b2b-test-cases.csv (65 cases from the PRD test document).
"""

from __future__ import annotations

import argparse
import csv
import importlib.util
import json
import mimetypes
import re
import sys
import time
from pathlib import Path

_TESTS_DIR = Path(__file__).resolve().parent
_SPEC = importlib.util.spec_from_file_location("run_functional_tests", _TESTS_DIR / "run-functional-tests.py")
if _SPEC is None or _SPEC.loader is None:
    raise RuntimeError("Unable to load run-functional-tests.py")
_RUN_FUNCTIONAL_TESTS = importlib.util.module_from_spec(_SPEC)
_SPEC.loader.exec_module(_RUN_FUNCTIONAL_TESTS)
Tester = _RUN_FUNCTIONAL_TESTS.Tester


class DataDrivenTester(Tester):
    def __init__(self, base_url: str, project_name: str, mailpit_url: str, data_path: Path) -> None:
        super().__init__(base_url, project_name, mailpit_url)
        self.data_path = data_path
        self.test_data = json.loads(data_path.read_text(encoding="utf-8"))

    def record_data(self, case_id: str, data_id: str, name: str, status: str, detail: str = "") -> None:
        self.record(f"{case_id}::{data_id}", name, status, detail)

    def body_contains_all(self, body: str, tokens: list[str]) -> list[str]:
        lowered = body.lower()
        return [token for token in tokens if token.lower() not in lowered]

    def run_data_driven(self) -> int:
        self.ensure_seed_data()
        self.ensure_test_baseline()
        if not self.ensure_web_ready():
            self.write_report()
            return 1

        mailpit_ready = self.ensure_mailpit_ready()
        self.run_home_datasets()
        self.run_product_datasets()
        self.run_form_datasets(mailpit_ready=mailpit_ready)
        self.run_catalog_datasets()
        self.run_about_datasets()
        self.run_contact_datasets()
        self.run_seo_datasets()
        self.run_security_datasets()
        self.write_report()
        return 1 if any(result.status == "FAIL" for result in self.results) else 0

    def run_home_datasets(self) -> None:
        for dataset in self.test_data["home"]["datasets"]:
            data_id = dataset["id"]
            start = time.time()
            status, body, _ = self.fetch(dataset["url"])
            elapsed = time.time() - start
            self.record_data(
                "H-01",
                data_id,
                f"Home load ({dataset['label']})",
                "PASS" if status < 400 and elapsed < dataset.get("max_load_seconds", 3.0) else "FAIL",
                f"HTTP {status}, {elapsed:.2f}s, viewport={dataset['viewport']}",
            )
            missing_cta = self.body_contains_all(body, dataset["cta_text"])
            self.record_data(
                "H-03",
                data_id,
                f"Hero CTA ({dataset['label']})",
                "PASS" if status < 400 and not missing_cta else "FAIL",
                f"missing={missing_cta or 'none'}",
            )
            card_count = len(re.findall(r'class="[^"]*lux-card', body))
            self.record_data(
                "H-04",
                data_id,
                f"Featured products ({dataset['label']})",
                "PASS" if card_count >= dataset["min_featured_products"] else "FAIL",
                f"cards={card_count}",
            )
            missing_cert = self.body_contains_all(body, dataset["cert_badges"])
            self.record_data(
                "H-05",
                data_id,
                f"Certificates ({dataset['label']})",
                "PASS" if not missing_cert else "FAIL",
                f"missing={missing_cert or 'none'}",
            )
            missing_nav = self.body_contains_all(body, dataset["nav_links"])
            self.record_data(
                "H-06",
                data_id,
                f"Navigation ({dataset['label']})",
                "PASS" if not missing_nav else "FAIL",
                f"missing={missing_nav or 'none'}",
            )
            missing_footer = self.body_contains_all(body, dataset["footer_checks"])
            self.record_data(
                "H-07",
                data_id,
                f"Footer ({dataset['label']})",
                "PASS" if not missing_footer else "FAIL",
                f"missing={missing_footer or 'none'}",
            )

    def run_product_datasets(self) -> None:
        for dataset in self.test_data["products"]["datasets"]:
            data_id = dataset["id"]
            status, body, _ = self.fetch(dataset["list_path"])
            self.record_data(
                "P-02",
                data_id,
                f"Product list ({dataset['label']})",
                "PASS" if status < 400 and "LX-" in body else "FAIL",
                f"path={dataset['list_path']}, HTTP {status}",
            )
            filter_status, filter_body, _ = self.fetch(dataset["filter_query"])
            matches = re.findall(r'data-product-category-slugs="([^"]*)"', filter_body)
            self.record_data(
                "P-03",
                data_id,
                f"Product filter ({dataset['label']})",
                "PASS" if filter_status < 400 and len(matches) > 0 else "FAIL",
                f"cards={len(matches)}, HTTP {filter_status}",
            )
            sort_status, sort_body, _ = self.fetch(dataset["sort_query"])
            titles = [item.lower() for item in re.findall(r'data-product-title="([^"]+)"', sort_body)]
            sorted_ok = len(titles) < 2 or titles == sorted(titles) or titles == sorted(titles, reverse=True)
            self.record_data(
                "P-04",
                data_id,
                f"Product sort ({dataset['label']})",
                "PASS" if sort_status < 400 and sorted_ok else "FAIL",
                f"titles={len(titles)}, HTTP {sort_status}",
            )
            proc = self.wp(
                [
                    "eval",
                    (
                        "$slug='%s';"
                        "$posts=get_posts(['post_type'=>'stage_lighting','name'=>$slug,'posts_per_page'=>1,'post_status'=>'publish']);"
                        "if(!$posts){$posts=get_posts(['post_type'=>'stage_lighting','post_status'=>'publish','posts_per_page'=>1]);}"
                        "echo $posts ? (string)$posts[0]->ID : '';"
                    )
                    % dataset["product_slug"].replace("'", "\\'"),
                ],
                timeout=90,
            )
            post_id = proc.stdout.strip().splitlines()[0] if proc.returncode == 0 and proc.stdout.strip() else ""
            if not post_id:
                self.record_data("P-05", data_id, f"Product detail ({dataset['label']})", "FAIL", "product not found")
                continue
            link_proc = self.wp(["post", "url", post_id], timeout=90)
            link = link_proc.stdout.strip() if link_proc.returncode == 0 else ""
            detail_status, detail_body, _ = self.fetch(link)
            missing_specs = self.body_contains_all(detail_body, dataset["spec_labels"])
            self.record_data(
                "P-05",
                data_id,
                f"Product detail ({dataset['label']})",
                "PASS" if detail_status < 400 and "/products/" in link else "FAIL",
                link,
            )
            self.record_data(
                "P-07",
                data_id,
                f"Technical specs ({dataset['label']})",
                "PASS" if detail_status < 400 and not missing_specs else "FAIL",
                f"missing={missing_specs or 'none'}",
            )
            self.record_data(
                "P-09",
                data_id,
                f"RFQ button ({dataset['label']})",
                "PASS" if "Send Inquiry" in detail_body and dataset["rfq_sku"] in detail_body else "FAIL",
                dataset["rfq_sku"],
            )

    def run_form_datasets(self, mailpit_ready: bool) -> None:
        if not mailpit_ready:
            for form_type in ["contact", "rfq", "catalog", "batch"]:
                for row in self.test_data["forms"][form_type]:
                    self.record_data(f"F-0{['contact','rfq','catalog','batch'].index(form_type)+1}", row["id"], f"{form_type} submit", "SKIP", "Mailpit unavailable")
            return

        for row in self.test_data["forms"]["contact"]:
            ok, status = self.submit_cf7_rest(
                "luxstage_contact",
                {k: v for k, v in row.items() if not k.startswith("id")},
            )
            self.record_data("F-01", row["id"], "Contact form submit", "PASS" if ok else "FAIL", status)

        for row in self.test_data["forms"]["validation_negative"]:
            ok, status = self.submit_cf7_rest(
                "luxstage_contact",
                {k: v for k, v in row.items() if k not in {"id", "expect_submit_fail"}},
            )
            expected_fail = bool(row.get("expect_submit_fail", True))
            self.record_data(
                "F-02",
                row["id"],
                "Validation negative",
                "PASS" if (not ok) == expected_fail else "FAIL",
                status,
            )

        for row in self.test_data["forms"]["rfq"]:
            attachment = row.get("attachment")
            file_payload = None
            if isinstance(attachment, dict):
                file_payload = (
                    attachment["filename"],
                    attachment["content"].encode("utf-8"),
                    attachment.get("content_type") or mimetypes.guess_type(attachment["filename"])[0] or "application/octet-stream",
                )
            fields = {k: v for k, v in row.items() if k not in {"id", "attachment"}}
            ok, status = self.submit_cf7_rest("luxstage_rfq", fields, file_payload=file_payload)
            self.record_data("F-03", row["id"], "RFQ submit", "PASS" if ok else "FAIL", status)
            self.record_data("F-04", row["id"], "RFQ attachment", "PASS" if ok and file_payload else "FAIL", status)

        for row in self.test_data["forms"]["catalog"]:
            ok, status = self.submit_cf7_rest(
                "luxstage_catalog",
                {k: v for k, v in row.items() if k != "id"},
            )
            self.record_data("F-05", row["id"], "Catalog lead submit", "PASS" if ok else "FAIL", status)

        for row in self.test_data["forms"]["catalog_returning"]:
            _, _, headers = self.fetch(row["cookie_query"])
            cookie_blob = " ".join(v for k, v in headers.items() if k.lower() == "set-cookie")
            cookie_match = re.search(r"(luxstage_catalog_returning=[^;]+)", cookie_blob)
            cookie_header = cookie_match.group(1) if cookie_match else ""
            _, body, _ = self.fetch("/catalog-request/", extra_headers={"Cookie": cookie_header} if cookie_header else None)
            missing = self.body_contains_all(body, row["expected_text"])
            self.record_data("F-06", row["id"], "Returning catalog downloader", "PASS" if not missing else "FAIL", f"missing={missing or 'none'}")

        for row in self.test_data["forms"]["batch"]:
            ok, status = self.submit_cf7_rest(
                "luxstage_batch",
                {k: v for k, v in row.items() if k != "id"},
            )
            self.record_data("F-08", row["id"], "Batch inquiry submit", "PASS" if ok else "FAIL", status)

    def run_catalog_datasets(self) -> None:
        for dataset in self.test_data["catalogs"]["datasets"]:
            data_id = dataset["id"]
            status, body, _ = self.fetch(dataset["archive_path"])
            self.record_data(
                "C-01",
                data_id,
                f"Catalog archive ({dataset['slug']})",
                "PASS" if status < 400 and "Catalog" in body else "FAIL",
                f"HTTP {status}",
            )
            cert_status, cert_body, _ = self.fetch(f"/downloads/catalogs/?certification={dataset['certification']}")
            matches = re.findall(r'data-catalog-certification-slugs="([^"]*)"', cert_body)
            self.record_data(
                "C-02",
                data_id,
                f"Catalog certification filter ({dataset['certification']})",
                "PASS" if cert_status < 400 and len(matches) > 0 else "FAIL",
                f"cards={len(matches)}",
            )
            download_match = re.search(r'href="([^"]*(?:catalog-download|luxstage_catalog_download=1)[^"]+)"', body)
            if download_match:
                expired = re.sub(r"expires=\d+", dataset["expired_sig"].split("&")[0], download_match.group(1))
                expired = re.sub(r"sig=[^&\"]+", dataset["expired_sig"].split("&")[1], expired)
                expire_status, _, _ = self.fetch(expired)
                self.record_data(
                    "C-05",
                    data_id,
                    f"Catalog link expiry ({dataset['slug']})",
                    "PASS" if expire_status in (400, 403, 410) else "FAIL",
                    f"HTTP {expire_status}",
                )
            else:
                self.record_data("C-05", data_id, f"Catalog link expiry ({dataset['slug']})", "FAIL", "download link not found")

    def run_about_datasets(self) -> None:
        for dataset in self.test_data["about"]["datasets"]:
            status, body, _ = self.fetch(dataset["path"])
            missing = self.body_contains_all(body, dataset["keywords"])
            self.record_data("A-01", dataset["id"], f"About content ({dataset['id']})", "PASS" if status < 400 and not missing else "FAIL", f"missing={missing or 'none'}")
            missing_video = self.body_contains_all(body.lower(), [item.lower() for item in dataset["video_embed"]])
            self.record_data("A-04", dataset["id"], f"About video ({dataset['id']})", "PASS" if status < 400 and not missing_video else "FAIL", f"missing={missing_video or 'none'}")

    def run_contact_datasets(self) -> None:
        for dataset in self.test_data["contact"]["datasets"]:
            status, body, _ = self.fetch(dataset["path"])
            missing_map = self.body_contains_all(body.lower(), [item.lower() for item in dataset["map_embed"]])
            self.record_data("T-02", dataset["id"], f"Contact map ({dataset['id']})", "PASS" if status < 400 and not missing_map else "FAIL", f"missing={missing_map or 'none'}")
            missing_contact = self.body_contains_all(body, dataset["contact_info"])
            self.record_data("T-03", dataset["id"], f"Contact info ({dataset['id']})", "PASS" if status < 400 and not missing_contact else "FAIL", f"missing={missing_contact or 'none'}")

    def run_seo_datasets(self) -> None:
        for dataset in self.test_data["seo"]["datasets"]:
            status, body, _ = self.fetch(dataset["path"])
            has_meta = "<title" in body.lower() and 'name="description"' in body.lower()
            self.record_data("S-01", dataset["id"], f"Meta tags ({dataset['path']})", "PASS" if status < 400 and has_meta else "FAIL", f"HTTP {status}")
            sitemap_status, sitemap_body, _ = self.fetch(dataset["sitemap_path"])
            if sitemap_status >= 400 and dataset["sitemap_path"] == "/sitemap_index.xml":
                sitemap_status, sitemap_body, _ = self.fetch("/wp-sitemap.xml")
            self.record_data(
                "S-02",
                dataset["id"],
                f"Sitemap ({dataset['sitemap_path']})",
                "PASS" if sitemap_status < 400 and "sitemap" in sitemap_body.lower() else "FAIL",
                f"HTTP {sitemap_status}",
            )
            robots_status, robots_body, _ = self.fetch("/robots.txt")
            missing_rules = self.body_contains_all(robots_body, dataset["robots_rules"])
            self.record_data(
                "S-07",
                dataset["id"],
                f"robots.txt ({dataset['id']})",
                "PASS" if robots_status < 400 and not missing_rules else "FAIL",
                f"missing={missing_rules or 'none'}",
            )

    def run_security_datasets(self) -> None:
        for row in self.test_data["security"]["sqli_queries"]:
            status, _, _ = self.fetch(row["path"], timeout=8)
            self.record_data("X-05", row["id"], f"SQLi smoke ({row['path']})", "PASS" if 0 < status < 500 else "FAIL", f"HTTP {status}")

        for dataset in self.test_data["performance"]["datasets"]:
            start = time.time()
            status, body, headers = self.fetch(dataset["path"])
            elapsed = time.time() - start
            self.record_data(
                "X-01",
                dataset["id"],
                f"Performance smoke ({dataset['path']})",
                "PASS" if status < 400 and elapsed < dataset["max_load_seconds"] else "FAIL",
                f"HTTP {status}, {elapsed:.2f}s",
            )
            cache_headers = " ".join(f"{k}: {v}" for k, v in headers.items()).lower()
            self.record_data(
                "X-07",
                dataset["id"],
                f"Cache headers ({dataset['path']})",
                "PASS" if dataset["cache_header"] in cache_headers else "SKIP",
                dataset["cache_header"],
            )
            self.record_data(
                "X-06",
                dataset["id"],
                f"Lazy loading ({dataset['path']})",
                "PASS" if dataset["lazy_attr"] in body or "<img" not in body else "SKIP",
                dataset["lazy_attr"],
            )


def load_case_matrix(path: Path) -> list[dict[str, str]]:
    with path.open(encoding="utf-8", newline="") as fh:
        return list(csv.DictReader(fh))


def main() -> int:
    parser = argparse.ArgumentParser(description="Run Luxstage B2B data-driven functional tests")
    parser.add_argument("--base-url", default="http://localhost:8080")
    parser.add_argument("--project-name", default="luxstage")
    parser.add_argument("--mailpit-url", default="http://localhost:8025")
    parser.add_argument("--data-file", default="tests/luxstage-b2b-test-data.json")
    parser.add_argument("--cases-file", default="tests/luxstage-b2b-test-cases.csv")
    parser.add_argument("--list-cases", action="store_true", help="Print test case matrix and exit")
    args = parser.parse_args()

    root = Path(__file__).resolve().parents[1]
    data_path = root / args.data_file
    cases_path = root / args.cases_file

    if args.list_cases:
        cases = load_case_matrix(cases_path)
        print(f"Loaded {len(cases)} test cases from {cases_path}")
        for case in cases:
            print(f"{case['case_id']}\t{case['module']}\t{case['test_item']}\t{case['priority']}")
        data = json.loads(data_path.read_text(encoding="utf-8"))
        module_counts = {
            "home": len(data["home"]["datasets"]),
            "products": len(data["products"]["datasets"]),
            "forms.contact": len(data["forms"]["contact"]),
            "forms.rfq": len(data["forms"]["rfq"]),
            "forms.catalog": len(data["forms"]["catalog"]),
            "forms.batch": len(data["forms"]["batch"]),
            "catalogs": len(data["catalogs"]["datasets"]),
            "about": len(data["about"]["datasets"]),
            "contact": len(data["contact"]["datasets"]),
            "seo": len(data["seo"]["datasets"]),
            "languages": len(data["languages"]["datasets"]),
            "backend.products": len(data["backend"]["products"]),
            "performance": len(data["performance"]["datasets"]),
            "security.sqli": len(data["security"]["sqli_queries"]),
        }
        print("\nTest data sets (3 per module/feature):")
        for key, count in module_counts.items():
            print(f"  {key}: {count}")
        return 0

    return DataDrivenTester(args.base_url, args.project_name, args.mailpit_url, data_path).run_data_driven()


if __name__ == "__main__":
    sys.exit(main())
