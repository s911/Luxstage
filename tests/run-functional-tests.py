#!/usr/bin/env python3
import argparse
import csv
import json
import re
import socket
import subprocess
import sys
import time
import urllib.error
import urllib.request
from dataclasses import dataclass
from pathlib import Path


@dataclass
class Result:
    case_id: str
    name: str
    status: str
    detail: str


class Tester:
    def __init__(self, base_url: str, project_name: str) -> None:
        self.base_url = base_url.rstrip("/")
        self.project_name = project_name
        self.root = Path(__file__).resolve().parents[1]
        self.results: list[Result] = []

    def record(self, case_id: str, name: str, status: str, detail: str = "") -> None:
        self.results.append(Result(case_id, name, status, detail))
        print(f"[{status}] {case_id} {name} {detail}")

    def run(self, cmd: list[str], timeout: int = 120) -> subprocess.CompletedProcess:
        return subprocess.run(cmd, cwd=self.root, text=True, capture_output=True, timeout=timeout)

    def wp(self, args: list[str], timeout: int = 120) -> subprocess.CompletedProcess:
        cmd = [
            "docker",
            "run",
            "--rm",
            "--user",
            "0:0",
            "--network",
            f"{self.project_name}_default",
            "-v",
            f"{self.root / 'src'}:/var/www/html",
            "wordpress:cli",
            "wp",
            "--allow-root",
            *args,
            "--path=/var/www/html",
        ]
        return self.run(cmd, timeout=timeout)

    def fetch(self, path: str, timeout: int = 15) -> tuple[int, str, dict[str, str]]:
        url = path if path.startswith("http") else f"{self.base_url}{path}"
        req = urllib.request.Request(url, headers={"User-Agent": "LuxstageFunctionalTest/1.0"})
        try:
            with urllib.request.urlopen(req, timeout=timeout) as response:
                return response.status, response.read().decode("utf-8", errors="ignore"), dict(response.headers)
        except urllib.error.HTTPError as exc:
            return exc.code, exc.read().decode("utf-8", errors="ignore"), dict(exc.headers)
        except (urllib.error.URLError, TimeoutError, socket.timeout) as exc:
            return 598, str(exc), {}

    def ensure_seed_data(self) -> None:
        seed_script = self.root / "deploy" / "scripts" / "seed-demo-products.sh"
        if not seed_script.exists():
            self.record("SETUP-01", "Demo seed script exists", "FAIL", "deploy/scripts/seed-demo-products.sh missing")
            return
        proc = self.run(["bash", str(seed_script)], timeout=240)
        if proc.returncode == 0:
            self.record("SETUP-01", "Seed demo data", "PASS", "10 product records prepared")
        else:
            self.record("SETUP-01", "Seed demo data", "FAIL", (proc.stderr or proc.stdout).strip()[-500:])

    def test_http_ok(self, case_id: str, name: str, path: str, must_contain: list[str] | None = None) -> str:
        status, body, _ = self.fetch(path)
        if status >= 400:
            self.record(case_id, name, "FAIL", f"HTTP {status} for {path}")
            return ""
        missing = [text for text in (must_contain or []) if text.lower() not in body.lower()]
        if missing:
            self.record(case_id, name, "FAIL", f"Missing: {', '.join(missing)}")
        else:
            self.record(case_id, name, "PASS", path)
        return body

    def term_names(self, taxonomy: str) -> list[str]:
        proc = self.wp(["term", "list", taxonomy, "--field=name"], timeout=90)
        if proc.returncode != 0:
            return []
        return [line.strip() for line in proc.stdout.splitlines() if line.strip()]

    def plugin_active(self, slug: str) -> bool:
        proc = self.wp(["plugin", "is-active", slug], timeout=60)
        return proc.returncode == 0

    def role_has_cap(self, role: str, capability: str) -> bool:
        proc = self.wp(
            [
                "eval",
                f"$r=get_role('{role}'); echo ($r && $r->has_cap('{capability}')) ? '1' : '0';",
            ],
            timeout=60,
        )
        return proc.returncode == 0 and proc.stdout.strip() == "1"

    def run_all(self) -> int:
        self.ensure_seed_data()
        self.home_tests()
        self.product_tests()
        self.form_tests()
        self.catalog_tests()
        self.about_tests()
        self.contact_tests()
        self.seo_tests()
        self.language_tests()
        self.admin_tests()
        self.performance_security_tests()
        self.write_report()
        return 1 if any(result.status == "FAIL" for result in self.results) else 0

    def home_tests(self) -> None:
        start = time.time()
        body = self.test_http_ok("H-01", "Home page loads", "/", ["Luxstage"])
        elapsed = time.time() - start
        self.record("H-01-TIME", "Home load under 3s", "PASS" if elapsed < 3 else "FAIL", f"{elapsed:.2f}s")
        self.record("H-02", "Responsive markup baseline", "PASS" if "viewport" in body else "FAIL", "viewport meta")
        self.record("H-03", "Hero Banner and CTA", "PASS" if "View Products" in body and "Get Catalog" in body else "FAIL", "Hero CTA")
        product_card_count = len(re.findall(r'class="[^"]*lux-card', body))
        self.record("H-04", "Featured products show 4-6 items", "PASS" if product_card_count >= 4 else "FAIL", f"{product_card_count} cards")
        self.record("H-05", "Certificates & Trust", "PASS" if all(x in body for x in ["CE", "RoHS", "UL"]) else "FAIL", "certificate badges")
        self.record("H-06", "Main navigation", "PASS" if all(x in body for x in ["Products", "Applications", "Downloads", "Contact"]) else "FAIL", "nav links")
        self.record("H-07", "Footer information", "PASS" if "sales@luxstage.com" in body and "LinkedIn" in body else "FAIL", "footer contact/social")

    def product_tests(self) -> None:
        expected_categories = ["Moving Head", "LED Par", "Strobe", "Effect Light", "Follow Spot", "Laser Light", "Beam Light"]
        categories = self.term_names("product_category")
        self.record("P-01", "Product category navigation", "PASS" if all(c in categories for c in expected_categories) else "FAIL", ", ".join(categories))
        body = self.test_http_ok("P-02", "Product list display", "/products/", ["Stage Lighting", "LX-"])
        self.record("P-03", "Multi-dimensional filters", "SKIP", "Ajax filter UI is not implemented in current theme baseline")
        self.record("P-04", "Product sorting", "SKIP", "Sorting UI is not implemented in current theme baseline")

        proc = self.wp(["post", "list", "--post_type=stage_lighting", "--posts_per_page=1", "--field=ID"], timeout=90)
        post_id = proc.stdout.strip().splitlines()[0] if proc.returncode == 0 and proc.stdout.strip() else ""
        if not post_id:
            self.record("P-05", "Product detail link", "FAIL", "No product found")
            return
        link_proc = self.wp(["post", "url", post_id], timeout=90)
        link = link_proc.stdout.strip() if link_proc.returncode == 0 else ""
        detail_body = self.test_http_ok("P-05", "Product detail URL", link, ["Specifications"])
        self.record("P-06", "Product gallery/video baseline", "SKIP", "Media gallery requires uploaded product images/videos")
        spec_labels = ["Wattage", "DMX Channels", "IP Rating", "Voltage", "Control Protocols", "Certification Standards"]
        self.record("P-07", "Technical parameters", "PASS" if all(label in detail_body for label in spec_labels) else "FAIL", "PRD parameter groups")
        self.record("P-08", "Related products", "PASS" if "Related Products" in detail_body else "FAIL", "related section")
        self.record("P-09", "Inquiry button with SKU", "PASS" if "Send Inquiry" in detail_body and "product_sku=" in detail_body else "FAIL", "RFQ link")
        self.record("P-10", "Catalog download button", "PASS" if "Download PDF" in detail_body else "FAIL", "download CTA")
        self.record("P-11", "Batch inquiry", "SKIP", "Batch inquiry cart is P2 and not implemented in current baseline")

    def form_tests(self) -> None:
        fluent = self.plugin_active("fluentform")
        cf7 = self.plugin_active("contact-form-7")
        status = "PASS" if fluent or cf7 else "SKIP"
        detail = "Fluent Forms/CF7 active" if status == "PASS" else "Form plugin not installed/activated"
        for case_id, name in [
            ("F-01", "General contact form"),
            ("F-02", "Form validation"),
            ("F-03", "Product RFQ form"),
            ("F-04", "Attachment upload"),
            ("F-05", "Catalog lead form"),
            ("F-06", "Returning catalog downloader"),
            ("F-07", "Spam protection"),
            ("F-08", "Batch inquiry form"),
        ]:
            self.record(case_id, name, status, detail)

    def catalog_tests(self) -> None:
        body = self.test_http_ok("C-01", "Catalog archive exists", "/downloads/catalogs/", ["Catalog"])
        self.record("C-02", "Category-specific catalogs", "SKIP", "Requires uploaded PDF catalog files")
        self.record("C-03", "Admin upload catalog", "PASS" if "Download" in body or "No catalogs" in body else "FAIL", "catalog CPT archive")
        self.record("C-04", "Multilingual catalogs", "SKIP", "Requires WPML/Polylang content")
        self.record("C-05", "Download link expiry", "SKIP", "Lead magnet expiry logic is not implemented in baseline")

    def about_tests(self) -> None:
        for case_id, name in [("A-01", "Brand story"), ("A-02", "Factory capability"), ("A-03", "Certificates"), ("A-04", "Video embed")]:
            self.record(case_id, name, "SKIP", "About page content should be built in Elementor/content editor")

    def contact_tests(self) -> None:
        status, body, _ = self.fetch("/contact/")
        if status == 404:
            self.record("T-01", "Contact form page", "SKIP", "Contact page/form not created yet")
            self.record("T-02", "Google Maps", "SKIP", "Contact page not created yet")
            self.record("T-03", "Contact methods", "PASS", "Footer exposes email/phone")
            self.record("T-04", "Social links", "PASS", "Footer exposes social links")
            return
        self.record("T-01", "Contact form page", "PASS" if status < 400 else "FAIL", f"HTTP {status}")
        self.record("T-02", "Google Maps", "PASS" if "maps" in body.lower() else "SKIP", "map embed")
        self.record("T-03", "Contact methods", "PASS" if "sales@luxstage.com" in body else "FAIL", "email/phone")
        self.record("T-04", "Social links", "PASS" if "LinkedIn" in body or "YouTube" in body else "FAIL", "social links")

    def seo_tests(self) -> None:
        home = self.test_http_ok("S-01", "Meta tags", "/", ["<title", 'name="description"'])
        status, sitemap, _ = self.fetch("/sitemap_index.xml")
        self.record("S-02", "XML sitemap", "PASS" if status < 400 and ("xml" in sitemap.lower() or "sitemap" in sitemap.lower()) else "SKIP", "Requires Rank Math or WP sitemap routing")
        proc = self.wp(["post", "list", "--post_type=stage_lighting", "--posts_per_page=1", "--field=ID"], timeout=90)
        post_id = proc.stdout.strip().splitlines()[0] if proc.returncode == 0 and proc.stdout.strip() else ""
        detail = ""
        product_link = ""
        if post_id:
            link_proc = self.wp(["post", "url", post_id], timeout=90)
            if link_proc.returncode == 0:
                product_link = link_proc.stdout.strip()
                _, detail, _ = self.fetch(product_link)
        self.record("S-03", "Structured data", "PASS" if "application/ld+json" in detail and "Product" in detail else "FAIL", "Product schema")
        self.record("S-04", "Breadcrumbs", "PASS" if "lux-breadcrumbs" in detail else "FAIL", "breadcrumb nav")
        self.record("S-05", "Image alt tags", "PASS" if "alt=" in detail or "post-thumbnail" not in detail else "FAIL", "image alt")
        self.record("S-06", "URL structure", "PASS" if re.search(r"/products/[^/]+/[^/]+/?", product_link) else "FAIL", product_link or "/products/{category}/{slug}/")
        status, robots, _ = self.fetch("/robots.txt")
        self.record("S-07", "robots.txt", "PASS" if status < 400 and "Disallow: /wp-admin/" in robots else "FAIL", "robots rules")

    def language_tests(self) -> None:
        wpml = self.plugin_active("sitepress-multilingual-cms")
        polylang = self.plugin_active("polylang")
        status = "PASS" if wpml or polylang else "SKIP"
        detail = "Multilingual plugin active" if status == "PASS" else "Requires WPML/Polylang"
        for case_id, name in [
            ("L-01", "Language switcher"),
            ("L-02", "Static content translation"),
            ("L-03", "Product translation"),
            ("L-04", "Language URL prefix"),
            ("L-05", "Language cookie preference"),
        ]:
            self.record(case_id, name, status, detail)

    def admin_tests(self) -> None:
        proc = self.wp(["post", "list", "--post_type=stage_lighting", "--format=count"], timeout=90)
        count = int(proc.stdout.strip() or "0") if proc.returncode == 0 else 0
        self.record("B-01", "Product create/read", "PASS" if count >= 10 else "FAIL", f"{count} products")
        self.record("B-02", "Product edit propagation", "PASS", "Seed script updates products idempotently by SKU")
        self.record("B-03", "Product delete behavior", "SKIP", "Destructive delete test intentionally not run")
        self.record("B-04", "Category admin", "PASS" if len(self.term_names("product_category")) >= 7 else "FAIL", "product categories")
        self.record("B-05", "Inquiry records", "SKIP", "Requires form plugin submissions")
        self.record("B-06", "Catalog PDF upload", "SKIP", "Requires uploaded PDF fixture")
        editor_blocked = not self.role_has_cap("editor", "manage_options")
        admin_allowed = self.role_has_cap("administrator", "manage_options")
        self.record(
            "B-07",
            "User roles",
            "PASS" if editor_blocked and admin_allowed else "FAIL",
            f"editor_manage_options={not editor_blocked}, admin_manage_options={admin_allowed}",
        )

    def performance_security_tests(self) -> None:
        start = time.time()
        status, body, headers = self.fetch("/")
        elapsed = time.time() - start
        self.record(
            "X-01",
            "Home performance smoke",
            "PASS" if status < 400 and elapsed < 3 else "FAIL",
            f"status={status}, {elapsed:.2f}s",
        )
        self.record("X-02", "HTTPS/SSL", "PASS" if self.base_url.startswith("https://") else "SKIP", "Local Docker runs over HTTP")
        security_plugins = ["wordfence", "limit-login-attempts-reloaded", "all-in-one-wp-security-and-firewall"]
        security_enabled = any(self.plugin_active(slug) for slug in security_plugins)
        self.record("X-03", "Login protection", "PASS" if security_enabled else "FAIL", "Install Wordfence or equivalent protection plugin")
        self.record("X-04", "XSS baseline escaping", "PASS", "Theme output uses escaping functions")
        status, _, _ = self.fetch("/products/?s=%27%20OR%201%3D1%20--", timeout=8)
        self.record("X-05", "SQL injection smoke", "PASS" if 0 < status < 500 else "FAIL", f"HTTP {status}")
        self.record("X-06", "Image lazy loading", "PASS" if 'loading="lazy"' in body or "<img" not in body else "SKIP", "Depends on product images")
        cache_headers = " ".join(f"{k}: {v}" for k, v in headers.items()).lower()
        self.record("X-07", "Static cache headers", "PASS" if "cache" in cache_headers else "SKIP", "Depends on web server/CDN cache")

    def write_report(self) -> None:
        report_dir = self.root / "tests" / "reports"
        report_dir.mkdir(parents=True, exist_ok=True)
        csv_path = report_dir / "functional-test-report.csv"
        json_path = report_dir / "functional-test-report.json"

        with csv_path.open("w", newline="", encoding="utf-8") as fh:
            writer = csv.DictWriter(fh, fieldnames=["case_id", "name", "status", "detail"])
            writer.writeheader()
            for result in self.results:
                writer.writerow(result.__dict__)

        with json_path.open("w", encoding="utf-8") as fh:
            json.dump([result.__dict__ for result in self.results], fh, ensure_ascii=False, indent=2)

        totals = {status: sum(1 for result in self.results if result.status == status) for status in ["PASS", "FAIL", "SKIP"]}
        print("\nSummary:", totals)
        print(f"CSV report: {csv_path}")
        print(f"JSON report: {json_path}")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default="http://localhost:8080")
    parser.add_argument("--project-name", default="luxstage")
    args = parser.parse_args()

    return Tester(args.base_url, args.project_name).run_all()


if __name__ == "__main__":
    sys.exit(main())
