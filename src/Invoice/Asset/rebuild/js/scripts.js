"use strict";

/**
 * Converted from jQuery to vanilla JavaScript
 * jQuery has been removed from this project
 */

document.addEventListener('DOMContentLoaded', function () {    
    // used in inv/guest quote/partial_item_table inv/partial_item_table
    // Initialize Bootstrap 5 tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Select2 for all select inputs. used in modal_product_lookups.js
    // TODO: Consider replacing select2 with a vanilla JS alternative like Tom Select or Choices.js
    // For now, initialize select2 only if it's loaded to avoid errors
    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined') {
        window.jQuery('.simple-select').select2();
    }
    
    // used in inv/index quote/index and numberous other places e.g. $toolbarReset
    document.addEventListener('click', function (e) {
        if (e.target.closest('.ajax-loader')) {
            const loader = document.getElementById('fullpage-loader');
            if (loader) {
                loader.style.display = 'block';
                loader.style.opacity = '1';
            }
            window.fullpageloaderTimeout = window.setTimeout(function () {
                const loaderError = document.getElementById('loader-error');
                const loaderIcon = document.getElementById('loader-icon');
                if (loaderError) {
                    loaderError.style.display = 'block';
                    loaderError.style.opacity = '1';
                }
                if (loaderIcon) {
                    loaderIcon.classList.remove('fa-spin');
                    loaderIcon.classList.add('text-danger');
                }
            }, 10000);
        }
    });
    
    // used in resources/views/layout/invoice
    document.addEventListener('click', function (e) {
        if (e.target.closest('.fullpage-loader-close')) {
            const loader = document.getElementById('fullpage-loader');
            const loaderError = document.getElementById('loader-error');
            const loaderIcon = document.getElementById('loader-icon');
            
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => { loader.style.display = 'none'; }, 200);
            }
            if (loaderError) {
                loaderError.style.display = 'none';
            }
            if (loaderIcon) {
                loaderIcon.classList.add('fa-spin');
                loaderIcon.classList.remove('text-danger');
            }
            clearTimeout(window.fullpageloaderTimeout);
        }
    });
    
    // used in emailtemplate.js
    const password_input = document.querySelector('.passwordmeter-input');
    if (password_input) {
        password_input.addEventListener('input', function(){
            const strength = zxcvbn(password_input.value);

            const passmeter2Elements = document.querySelectorAll('.passmeter-2');
            const passmeter3Elements = document.querySelectorAll('.passmeter-3');
            
            passmeter2Elements.forEach(el => el.style.display = 'none');
            passmeter3Elements.forEach(el => el.style.display = 'none');
            
            if (strength.score === 4) {
                passmeter2Elements.forEach(el => el.style.display = 'block');
                passmeter3Elements.forEach(el => el.style.display = 'block');
            } else if (strength.score === 3) {
                passmeter2Elements.forEach(el => el.style.display = 'block');
            }
        });
    }
});
