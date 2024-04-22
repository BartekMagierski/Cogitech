const autoCloseBox = {
  fadeDuration: 300,
  slideDuration: 100,
  init: function(durration) {

    let autoCloseElements = document.querySelectorAll(".auto-close");

    setTimeout( () => {
      autoCloseElements.forEach( (element) => {
        if(!element.hasAttribute("running")) {
          element.setAttribute("running", true);
          this.fadeAndSlide(element);
        }
      });
    }, durration && typeof durration === "number" ? durration : 2000 );

    return true;

  },
  fadeAndSlide: function(element) {
    // Step 1: Fade out the element
    let opacity = 1;
    const fadeInterval = setInterval( () => {

      if (opacity > 0) {
  
        opacity -= 0.1;
        element.style.opacity = opacity;
  
      } else {
          clearInterval(fadeInterval);
          // Step 2: Slide up the element
          let height = element.offsetHeight;
          const slideInterval = setInterval( () => {
  
              if (height > 0) {

                height -= 10;
                element.style.height = height + "px";
  
              } else {
  
                  clearInterval(slideInterval);
                  // Step 3: Remove the element from the DOM
                  element.parentNode.removeChild(element);
  
              }
  
          }, this.slideDuration / 10);
      }
    }, this.fadeDuration / 10);
  }
};