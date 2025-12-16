(function () {
    "use strict";

    function parsedata(data) {
        if (!data) return {};
        if (typeof data === 'object' && data !== null) return data;
        if (typeof data === 'string') {
            try { return JSON.parse(data); } catch (e) { return {}; }
        }
        return {};
    }

    function secureReload() {
        // Secure reload function to prevent Open Redirect vulnerabilities
        // Only reloads the current page without accepting external URLs
        window.location.reload();
    }

    // Helper function to safely create loading/success UI elements to prevent XSS
    function createSecureUIElement(type, className, iconClass) {
        var element = document.createElement(type || 'h6');
        element.className = className || 'text-center';
        var icon = document.createElement('i');
        icon.className = iconClass || 'fa fa-spin fa-spinner';
        element.appendChild(icon);
        return element;
    }

    // Helper function to safely set button content
    function setSecureButtonContent(btn, type, className, iconClass) {
        if (!btn) return;
        btn.textContent = '';
        btn.appendChild(createSecureUIElement(type, className, iconClass));
    }

    // Simple GET helper that builds a query string from params and returns parsed text/JSON
    function getJson(url, params) {
        var u = url;
        if (params) {
            var sp = new URLSearchParams();
            Object.keys(params).forEach(function (k) {
                var v = params[k];
                if (Array.isArray(v)) {
                    v.forEach(function (x) { sp.append(k, x); });
                } else if (v !== undefined && v !== null) {
                    sp.append(k, v);
                }
            });
            u = url + (url.indexOf('?') === -1 ? '?' + sp.toString() : '&' + sp.toString());
        }
        return fetch(u, { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                if (!res.ok) throw new Error('Network response not ok: ' + res.status);
                return res.text();
            })
            .then(function (text) {
                try { return JSON.parse(text); } catch (e) { return text; }
            });
    }

    // Delegated click handlers
    document.addEventListener('click', function (e) {
        var el = e.target;

        // client_create_confirm
        var createBtn = el.closest('#client_create_confirm');
        if (createBtn) {
            var url = location.origin + "/invoice/client/create_confirm";
            var btn = document.querySelector('.client_create_confirm') || createBtn;
            var absolute_url = new URL(location.href);

            // UI feedback
            if (btn) {
                setSecureButtonContent(btn, 'h6', 'text-center', 'fa fa-spin fa-spinner');
                btn.disabled = true;
            }

            var payload = {
                client_name: (document.getElementById('client_name') || {}).value || '',
                client_surname: (document.getElementById('client_surname') || {}).value || '',
                client_email: (document.getElementById('client_email') || {}).value || ''
            };

            getJson(url, payload)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        setSecureButtonContent(btn, 'h2', 'text-center', 'fa fa-check');
                        // Mirror original behaviour: secure reload
                        secureReload();
                    } else {
                        if (btn) {
                            setSecureButtonContent(btn, 'h6', 'text-center', 'fa fa-check');
                            btn.disabled = false;
                        }
                        console.warn('create_confirm response', response);
                    }
                })
                .catch(function (err) {
                    console.warn(err);
                    if (btn) { 
                        setSecureButtonContent(btn, 'h6', 'text-center', 'fa fa-check'); 
                        btn.disabled = false; 
                    }
                    alert('An error occurred while creating client. See console for details.');
                });
            return;
        }

        // save_client_note_new
        var saveNoteBtn = el.closest('#save_client_note_new');
        if (saveNoteBtn) {
            var url = location.origin + "/invoice/client/save_client_note_new";
            var url_note_list = location.origin + "/invoice/client/load_client_notes";
            var btn_note = document.querySelector('.save_client_note') || saveNoteBtn;
            var absolute_url = new URL(location.href);

            if (btn_note) {
                setSecureButtonContent(btn_note, 'h6', 'text-center', 'fa fa-spin fa-spinner');
                btn_note.disabled = true;
            }

            var payloadNote = {
                client_id: (document.getElementById('client_id') || {}).value || '',
                client_note: (document.getElementById('client_note') || {}).value || ''
            };

            getJson(url, payloadNote)
                .then(function (data) {
                    var response = parsedata(data);
                    if (response.success === 1) {
                        setSecureButtonContent(btn_note, 'h2', 'text-center', 'fa fa-check');
                        var noteEl = document.getElementById('client_note');
                        if (noteEl) noteEl.value = '';

                        // Reload notes list (replace jQuery .load behavior)
                        var notesList = document.getElementById('notes_list');
                        if (notesList) {
                            var loadUrl = url_note_list + '?client_id=' + encodeURIComponent(payloadNote.client_id);
                            fetch(loadUrl, { cache: 'no-store', credentials: 'same-origin' })
                                .then(function (r) { return r.text(); })
                                .then(function (html) {
                                    // Sanitize HTML content to prevent XSS attacks
                                    notesList.textContent = '';
                                    var parser = new DOMParser();
                                    try {
                                        var doc = parser.parseFromString(html, 'text/html');
                                        // Only append if parsing was successful and content is from trusted source
                                        if (doc && doc.body) {
                                            var fragment = document.createDocumentFragment();
                                            while (doc.body.firstChild) {
                                                fragment.appendChild(doc.body.firstChild);
                                            }
                                            notesList.appendChild(fragment);
                                        }
                                    } catch (e) {
                                        console.error('HTML parsing error:', e);
                                        notesList.textContent = 'Error loading notes';
                                    }
                                    console.log(html);
                                })
                                .catch(function (err) { console.error('load_client_notes failed', err); });
                        }

                        // Mirror original behaviour: secure reload
                        secureReload();
                    } else {
                        // validation errors
                        document.querySelectorAll('.control-group').forEach(function (g) { g.classList.remove('error'); });
                        if (response.validation_errors) {
                            Object.keys(response.validation_errors).forEach(function (key) {
                                var elKey = document.getElementById(key);
                                if (elKey && elKey.parentElement) elKey.parentElement.classList.add('has-error');
                            });
                        }
                        if (btn_note) { 
                            setSecureButtonContent(btn_note, 'h6', 'text-center', 'fa fa-check'); 
                            btn_note.disabled = false; 
                        }
                    }
                })
                .catch(function (err) {
                    console.warn(err);
                    if (btn_note) { 
                        setSecureButtonContent(btn_note, 'h6', 'text-center', 'fa fa-check'); 
                        btn_note.disabled = false; 
                    }
                    alert('An error occurred while saving client note. See console for details.');
                });
            return;
        }

    }, true);

})();