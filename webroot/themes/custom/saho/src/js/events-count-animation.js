/**
 * @file
 * Animates event count badges from 0 to actual count
 */

((Drupal, once) => {
  /**
   * Animate counter from 0 to target value
   */
  function animateCount(element, target, duration = 1000) {
    const start = 0;
    const startTime = performance.now();

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);

      // Ease out cubic for smooth deceleration
      const easeProgress = 1 - (1 - progress) ** 3;
      const current = Math.round(start + (target - start) * easeProgress);

      element.textContent = current;

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        element.textContent = target;
      }
    }

    requestAnimationFrame(update);
  }

  Drupal.behaviors.eventsCountAnimation = {
    attach: (context) => {
      once('events-count-animation', '.events-count', context).forEach((counter) => {
        const target = Number.parseInt(
          counter.getAttribute('data-count') || counter.textContent,
          10
        );

        if (!Number.isNaN(target)) {
          // Store original value as data attribute if not already set
          if (!counter.hasAttribute('data-count')) {
            counter.setAttribute('data-count', target);
          }

          // Start animation after a short delay for better UX
          setTimeout(() => {
            animateCount(counter, target, 800);
          }, 100);
        }
      });
    },
  };
})(Drupal, once);
