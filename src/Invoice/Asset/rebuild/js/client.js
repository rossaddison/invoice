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
                btn.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
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
                        if (btn) btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        // Mirror original behaviour: navigate/reload
                        window.location = absolute_url;
                        window.location.reload();
                    } else {
                        if (btn) {
                            btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
                            btn.disabled = false;
                        }
                        console.warn('create_confirm response', response);
                    }
                })
                .catch(function (err) {
                    console.warn(err);
                    if (btn) { btn.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>'; btn.disabled = false; }
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
                btn_note.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
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
                        if (btn_note) btn_note.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                        var noteEl = document.getElementById('client_note');
                        if (noteEl) noteEl.value = '';

                        // Reload notes list (replace jQuery .load behavior)
                        var notesList = document.getElementById('notes_list');
                        if (notesList) {
                            var loadUrl = url_note_list + '?client_id=' + encodeURIComponent(payloadNote.client_id);
                            fetch(loadUrl, { cache: 'no-store', credentials: 'same-origin' })
                                .then(function (r) { return r.text(); })
                                .then(function (html) {
                                    notesList.innerHTML = html;
                                    console.log(html);
                                })
                                .catch(function (err) { console.error('load_client_notes failed', err); });
                        }

                        // Mirror original behaviour: navigate/reload
                        window.location = absolute_url;
                        window.location.reload();
                    } else {
                        // validation errors
                        document.querySelectorAll('.control-group').forEach(function (g) { g.classList.remove('error'); });
                        if (response.validation_errors) {
                            Object.keys(response.validation_errors).forEach(function (key) {
                                var elKey = document.getElementById(key);
                                if (elKey && elKey.parentElement) elKey.parentElement.classList.add('has-error');
                            });
                        }
                        if (btn_note) { btn_note.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>'; btn_note.disabled = false; }
                    }
                })
                .catch(function (err) {
                    console.warn(err);
                    if (btn_note) { btn_note.innerHTML = '<h6 class="text-center"><i class="fa fa-check"></i></h6>'; btn_note.disabled = false; }
                    alert('An error occurred while saving client note. See console for details.');
                });
            return;
        }

    }, true);

})();