/**
 * Converted from jQuery to vanilla JavaScript
 * jQuery has been removed from this project
 */

document.addEventListener('DOMContentLoaded', function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    }

    // id="client_create_confirm button on views/invoice/client/modal_create_client.php
    document.addEventListener('click', function (e) {
        if (e.target.id === 'client_create_confirm' || e.target.closest('#client_create_confirm')) {
            const url = window.location.origin + "/invoice/client/create_confirm";
            const btn = document.querySelector('.client_create_confirm');
            const absolute_url = new URL(window.location.href);
            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            
            const params = new URLSearchParams({
                client_name: document.getElementById('client_name').value,
                client_surname: document.getElementById('client_surname').value,
                client_email: document.getElementById('client_email').value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8'
                }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    // The validation was successful and quote was created
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';                        
                    window.location = absolute_url;
                    window.location.reload();
                }
            })
            .catch(error => {
                console.warn(error);
                alert('An error occurred: ' + error.toString());
            });
        }
    });
    
    // id="save_client_note_new button on views/invoice/client/view.php
    document.addEventListener('click', function (e) {
        if (e.target.id === 'save_client_note_new' || e.target.closest('#save_client_note_new')) {
            const url = window.location.origin + "/invoice/client/save_client_note_new";
            const url_note_list = window.location.origin + "/invoice/client/load_client_notes";
            const btn_note = document.querySelector('.save_client_note');
            const absolute_url = new URL(window.location.href);
            btn_note.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
            
            const client_id_elem = document.getElementById('client_id');
            const client_note_elem = document.getElementById('client_note');
            
            const params = new URLSearchParams({
                client_id: client_id_elem.value,
                client_note: client_note_elem.value
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8'
                }
            })
            .then(response => response.json())
            .then(data => {
                const response = parsedata(data);
                if (response.success === 1) {
                    // The validation was successful
                    btn_note.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                    client_note_elem.value = '';
                    
                    // Load notes list
                    const notes_list = document.getElementById('notes_list');
                    if (notes_list) {
                        const noteParams = new URLSearchParams({
                            client_id: client_id_elem.value
                        });
                        fetch(url_note_list + '?' + noteParams.toString())
                            .then(response => response.text())
                            .then(html => {
                                notes_list.innerHTML = html;
                                console.log(html);
                            });
                    }
                    
                    window.location = absolute_url;
                    window.location.reload();
                } else {
                    // The validation was not successful
                    document.querySelectorAll('.control-group').forEach(el => {
                        el.classList.remove('error');
                    });
                    for (var key in response.validation_errors) {
                        const elem = document.getElementById(key);
                        if (elem && elem.parentElement) {
                            elem.parentElement.classList.add('has-error');
                        }
                    }
                }
            })
            .catch(error => {
                console.warn(error);
                alert('An error occurred: ' + error.toString());
            });
        }
    });
});
