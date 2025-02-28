// script.js

document.addEventListener('DOMContentLoaded', function() {
  // Dark Mode Toggle
  const toggleDarkModeBtn = document.getElementById('toggleDarkModeBtn');
  const icon = toggleDarkModeBtn ? toggleDarkModeBtn.querySelector('i') : null;
  
  // Check for saved dark mode preference
  const isDarkMode = localStorage.getItem('darkMode') === 'true';
  
  // Apply saved preference on page load
  if (isDarkMode) {
    document.body.classList.add('dark-mode');
    if (icon) {
      icon.classList.remove('bi-moon-stars');
      icon.classList.add('bi-sun');
    }
  }
  
  // Dark mode toggle button event listener
  if (toggleDarkModeBtn) {
    toggleDarkModeBtn.addEventListener('click', function() {
      document.body.classList.toggle('dark-mode');
      
      // Update icon
      if (document.body.classList.contains('dark-mode')) {
        icon.classList.remove('bi-moon-stars');
        icon.classList.add('bi-sun');
        localStorage.setItem('darkMode', 'true');
      } else {
        icon.classList.remove('bi-sun');
        icon.classList.add('bi-moon-stars');
        localStorage.setItem('darkMode', 'false');
      }
    });
  }
  
  // Success alert animation
  const successAlert = document.getElementById('successAlert');
  if (successAlert) {
    successAlert.style.opacity = 0;
    successAlert.classList.add('fade-in');
    setTimeout(() => {
      successAlert.style.transition = 'opacity 0.5s';
      successAlert.style.opacity = 1;
    }, 200);
    
    // Auto hide after 5 seconds
    setTimeout(() => {
      successAlert.style.opacity = 0;
      setTimeout(() => {
        if (successAlert.parentNode) {
          successAlert.parentNode.removeChild(successAlert);
        }
      }, 500);
    }, 5000);
  }
  
  // Form validation enhancement
  const forms = document.querySelectorAll('.needs-validation');
  if (forms.length > 0) {
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }
  
  // Password visibility toggle
  const passwordToggles = document.querySelectorAll('.password-toggle');
  if (passwordToggles.length > 0) {
    passwordToggles.forEach(toggle => {
      toggle.addEventListener('click', function() {
        const input = document.getElementById(this.getAttribute('data-target'));
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('bi-eye');
          icon.classList.add('bi-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.remove('bi-eye-slash');
          icon.classList.add('bi-eye');
        }
      });
    });
  }
  
  // Calendar date selection
  const calendarDays = document.querySelectorAll('.calendar-day');
  if (calendarDays.length > 0) {
    calendarDays.forEach(day => {
      day.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.03)';
        this.style.zIndex = '1';
      });
      
      day.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.zIndex = '0';
      });
    });
  }
  
  // Time slot selector
  const timeSlotSelect = document.getElementById('time_slot');
  if (timeSlotSelect) {
    timeSlotSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption.value) {
        document.querySelector('.btn-cool[type="submit"]').removeAttribute('disabled');
      } else {
        document.querySelector('.btn-cool[type="submit"]').setAttribute('disabled', 'disabled');
      }
    });
    
    // Initialize state
    if (timeSlotSelect.value === '') {
      const submitButton = document.querySelector('.btn-cool[type="submit"]');
      if (submitButton) {
        submitButton.setAttribute('disabled', 'disabled');
      }
    }
  }
  
  // Add animation to cards
  const cards = document.querySelectorAll('.custom-card');
  if (cards.length > 0) {
    cards.forEach((card, index) => {
      setTimeout(() => {
        card.classList.add('slide-up');
      }, index * 100);
    });
  }
  
  // Confirm delete account action
  const deleteAccountBtn = document.querySelector('.btn-danger[value="yes"]');
  if (deleteAccountBtn) {
    deleteAccountBtn.addEventListener('click', function(event) {
      if (!confirm('Êtes-vous absolument sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) {
        event.preventDefault();
      }
    });
  }
  
  // Tooltips initialization
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  if (tooltipTriggerList.length > 0) {
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }
});