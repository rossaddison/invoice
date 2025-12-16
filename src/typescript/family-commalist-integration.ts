// Family Commalist Angular Integration
declare global {
  interface Window {
    toggleCommalistPicker: () => void;
  }
}

let angularAppMounted = false;

export function initializeCommalistPicker() {
  // Add the toggle function to global scope
  window.toggleCommalistPicker = toggleCommalistPicker;
  
  // Auto-show picker if there's existing data
  const textarea = document.getElementById('family_commalist') as HTMLTextAreaElement;
  if (textarea && textarea.value.trim()) {
    // Don't auto-show, let user decide
  }
}

function toggleCommalistPicker() {
  const container = document.getElementById('commalist-picker-container');
  const button = document.getElementById('toggle-picker-btn');
  
  if (!container || !button) return;
  
  if (container.style.display === 'none') {
    // Show picker
    container.style.display = 'block';
    button.innerHTML = '<i class="bi bi-grid-3x3-gap-fill"></i> Hide Number Picker';
    
    // Load Angular app if not already loaded
    if (!angularAppMounted) {
      loadAngularApp();
    }
  } else {
    // Hide picker
    container.style.display = 'none';
    button.innerHTML = '<i class="bi bi-grid-3x3-gap"></i> Show Number Picker';
  }
}

async function loadAngularApp() {
  try {
    const container = document.getElementById('commalist-picker-container');
    if (!container) return;
    
    // Create app root element
    const appRoot = document.createElement('app-root');
    
    // Find the info alert and insert after it
    const infoAlert = container.querySelector('.alert');
    if (infoAlert && infoAlert.nextSibling) {
      container.insertBefore(appRoot, infoAlert.nextSibling);
    } else {
      container.appendChild(appRoot);
    }
    
    // Load Angular bundle
    const angularScript = document.createElement('script');
    angularScript.src = '/invoice/angular/dist/main.js'; // We'll build this
    angularScript.type = 'module';
    angularScript.onload = () => {
      console.log('Angular commalist picker loaded successfully');
      angularAppMounted = true;
    };
    angularScript.onerror = (error) => {
      console.error('Failed to load Angular commalist picker:', error);
      container.innerHTML += '<div class="alert alert-warning">Number picker could not be loaded. Please use the textarea above.</div>';
    };
    
    document.head.appendChild(angularScript);
    
  } catch (error) {
    console.error('Error loading Angular app:', error);
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeCommalistPicker);
} else {
  initializeCommalistPicker();
}