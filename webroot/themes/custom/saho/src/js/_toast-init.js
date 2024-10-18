import Toast from 'bootstrap/js/dist/toast';

// * Run toasts on page load
(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const toastElList = [...document.querySelectorAll(".toast")];
    for (const toastEl of toastElList) {
      const toast = new Toast(toastEl, { autohide: false });
      toast.show();
    }
  });
})();

// * Run toasts on button trigger example
// (() => {
//   document.addEventListener("DOMContentLoaded", () => {
//     const toastTrigger = document.getElementById('liveToastBtn');
//     const toastLiveExample = document.getElementById('liveToast');
  
//     if (toastTrigger && toastLiveExample) {
//       const toastBootstrap = Toast.getOrCreateInstance(toastLiveExample);
//       toastTrigger.addEventListener('click', () => {
//         toastBootstrap.show();
//       });
//     }
//   });
// })();
