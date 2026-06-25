(function () {
  "use strict";

  document.documentElement.classList.add("js-enabled");

  function createLightbox() {
    var existing = document.querySelector("[data-lux-lightbox]");
    if (existing) {
      return existing;
    }

    var lightbox = document.createElement("div");
    lightbox.className = "lux-lightbox";
    lightbox.setAttribute("data-lux-lightbox", "1");
    lightbox.setAttribute("aria-hidden", "true");

    var closeBtn = document.createElement("button");
    closeBtn.type = "button";
    closeBtn.className = "lux-lightbox__close";
    closeBtn.setAttribute("aria-label", "Close image preview");
    closeBtn.textContent = "×";

    var image = document.createElement("img");
    image.className = "lux-lightbox__image";
    image.setAttribute("alt", "");

    lightbox.appendChild(closeBtn);
    lightbox.appendChild(image);
    document.body.appendChild(lightbox);

    function close() {
      lightbox.classList.remove("is-open");
      lightbox.setAttribute("aria-hidden", "true");
      image.setAttribute("src", "");
    }

    closeBtn.addEventListener("click", close);
    lightbox.addEventListener("click", function (event) {
      if (event.target === lightbox) {
        close();
      }
    });
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        close();
      }
    });

    lightbox.open = function (src, alt) {
      if (!src) {
        return;
      }
      image.setAttribute("src", src);
      image.setAttribute("alt", alt || "");
      lightbox.classList.add("is-open");
      lightbox.setAttribute("aria-hidden", "false");
    };

    return lightbox;
  }

  function initProductGalleries() {
    var galleries = document.querySelectorAll("[data-lux-product-gallery]");
    var lightbox = createLightbox();

    galleries.forEach(function (gallery) {
      var mainImage = gallery.querySelector("[data-lux-gallery-main]");
      var thumbs = gallery.querySelectorAll("[data-lux-gallery-thumb]");

      if (!mainImage || !thumbs.length) {
        return;
      }

      mainImage.addEventListener("click", function () {
        lightbox.open(mainImage.getAttribute("src"), mainImage.getAttribute("alt"));
      });

      thumbs.forEach(function (thumb) {
        thumb.addEventListener("click", function () {
          var nextSrc = thumb.getAttribute("data-full");
          var nextAlt = thumb.getAttribute("data-alt") || "";

          if (!nextSrc || mainImage.getAttribute("src") === nextSrc) {
            return;
          }

          thumbs.forEach(function (item) {
            item.classList.remove("is-active");
          });
          thumb.classList.add("is-active");

          mainImage.classList.add("is-switching");
          window.setTimeout(function () {
            mainImage.setAttribute("src", nextSrc);
            mainImage.setAttribute("alt", nextAlt);
            mainImage.classList.remove("is-switching");
          }, 140);
        });
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initProductGalleries);
  } else {
    initProductGalleries();
  }
})();
