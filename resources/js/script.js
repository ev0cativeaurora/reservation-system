// script.js

// Example: Toggle dark mode by adding/removing a class on the <body> element
document.addEventListener('DOMContentLoaded', function() {
    const toggleDarkModeBtn = document.getElementById('toggleDarkModeBtn');
    if (toggleDarkModeBtn) {
      toggleDarkModeBtn.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
      });
    }
    
    // Another example: Animate a success alert or fade something in/out
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
      successAlert.style.opacity = 0;
      setTimeout(() => {
        successAlert.style.transition = 'opacity 0.5s';
        successAlert.style.opacity = 1;
      }, 200);
    }
  });
  