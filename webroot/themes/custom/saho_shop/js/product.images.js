(($, Drupal) => {
  Drupal.behaviors.productGallery = {
    attach(context) {
      once('productGallery', '.image-item img', context).forEach(function (
        image,
      ) {
        const activeImage = document.querySelector('.product-image img');

        image.addEventListener('click', function (e) {
          const el = e.target;
          activeImage.src = el.src;
          const imageList = el.closest('.image-list');
          const imageItem = el.closest('.image-item');

          [...imageList.children].forEach((sib) =>
            sib.classList.remove('active'),
          );
          imageItem.classList.add('active');
        });
      });
    },
  };
})(jQuery, Drupal);
