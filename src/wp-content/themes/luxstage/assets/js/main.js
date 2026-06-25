(function () {
  "use strict";

  document.documentElement.classList.add("js-enabled");

  function initProductGalleries() {
    var galleries = document.querySelectorAll("[data-lux-product-gallery]");

    galleries.forEach(function (gallery) {
      var mainImage = gallery.querySelector("[data-lux-gallery-main]");
      var thumbs = gallery.querySelectorAll("[data-lux-gallery-thumb]");

      if (!mainImage || !thumbs.length) {
        return;
      }

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
