import './bootstrap';

function element(id) {
    return document.getElementById(id);
}

const editorDialog = element('editor-dialog');
const readerDialog = element('reader-dialog');
const deleteDialog = element('delete-dialog');
const notesList = element('notes-list');
const noteForm = element('note-form');
let currentPage = 1;
let lastPage = 1;
let activeQuery = '';
let editingNoteId = null;
let selectedNote = null;
let saving = false;
let deleting = false;
let processingAi = false;
let listRequestNumber = 0;
let noteRequestNumber = 0;
let notificationTimer;
let searchTimer;

async function apiRequest(path, options = {}) {
    let response;

    try {
        response = await fetch(path, {
            ...options,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        });
    } catch {
        throw new Error(
            'Could not connect. Check your connection and try again.',
        );
    }

    if (response.status === 204) {
        return null;
    }

    let data;

    try {
        data = await response.json();
    } catch {
        throw new Error(
            'The server returned an unexpected response. Please try again.',
        );
    }

    if (!response.ok) {
        const error = new Error(
            data.message || 'Something went wrong. Please try again.',
        );
        error.errors = data.errors || {};
        throw error;
    }

    return data;
}

function showMessage(id, message) {
    element(id).textContent = message;
    element(id).hidden = !message;
}

function notify(message, warning = false) {
    clearTimeout(notificationTimer);
    element('notification-text').textContent = message;
    element('notification').classList.toggle('warning', warning);
    element('notification').hidden = false;

    if (!warning) {
        notificationTimer = setTimeout(
            () => (element('notification').hidden = true),
            5000,
        );
    }
}

function shortDate(value) {
    return new Intl.DateTimeFormat('en', {
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}

async function loadNotes(page = 1) {
    const requestNumber = ++listRequestNumber;
    const query = activeQuery;
    const parameters = new URLSearchParams({ page, limit: 6 });

    if (query) {
        parameters.set('q', query);
    }

    element('loading-state').hidden = false;
    notesList.setAttribute('aria-busy', 'true');
    notesList.replaceChildren();
    element('empty-state').hidden = true;
    element('pagination').hidden = true;
    showMessage('list-error', '');

    try {
        const path = query ? '/api/notes/search' : '/api/notes';
        const result = await apiRequest(path + '?' + parameters);

        // Ignore an older search response if another request has already started.
        if (requestNumber !== listRequestNumber) {
            return;
        }

        currentPage = result.meta.current_page;
        lastPage = result.meta.last_page;

        if (result.data.length === 0 && currentPage > 1) {
            await loadNotes(currentPage - 1);
            return;
        }

        renderNotes(result.data);
        element('notes-heading').textContent = query
            ? 'Search results'
            : 'All notes';
        element('notes-count').textContent = result.meta.total;
        element('sort-label').textContent = query
            ? 'Most relevant first'
            : 'Recently updated';

        if (!query) {
            element('sidebar-count').textContent = result.meta.total;
        }

        const empty = result.data.length === 0;
        element('empty-state').hidden = !empty;
        element('empty-heading').textContent = query
            ? 'No matching thoughts yet.'
            : 'Your next idea starts here.';
        element('empty-description').textContent = query
            ? 'Try describing it another way. Notes still waiting for search preparation are not included.'
            : 'A quick thought, a useful discovery, a plan for tomorrow. Give it a place to live.';
        element('empty-new-note-button').hidden = Boolean(query);
        element('empty-clear-button').hidden = !query;
        element('pagination').hidden = result.meta.total === 0;
        element('pagination-description').textContent =
            `Showing ${result.meta.from}–${result.meta.to} of ${result.meta.total} notes`;
        element('page-label').textContent =
            `Page ${currentPage} of ${lastPage}`;
        element('previous-page-button').disabled = currentPage <= 1;
        element('next-page-button').disabled = currentPage >= lastPage;
    } catch (error) {
        if (requestNumber === listRequestNumber) {
            showMessage('list-error', error.message);
        }
    } finally {
        if (requestNumber === listRequestNumber) {
            element('loading-state').hidden = true;
            notesList.setAttribute('aria-busy', 'false');
        }
    }
}

function renderNotes(notes) {
    const cards = document.createDocumentFragment();

    for (const note of notes) {
        const card = element('note-card-template').content.cloneNode(true);
        const article = card.querySelector('.note-card');
        article.dataset.id = note.id;
        article.dataset.color = note.id % 4;
        card.querySelector('.note-title').textContent = note.title;
        card.querySelector('.note-excerpt').textContent = note.content;
        card.querySelector('.note-date').textContent = shortDate(
            note.updated_at,
        );
        card.querySelector('.note-date').dateTime = note.updated_at;
        card.querySelector('.note-card-open').setAttribute(
            'aria-label',
            'Open ' + note.title,
        );
        const state = card.querySelector('.note-state');
        state.textContent = note.search_ready
            ? 'Ready to find'
            : 'Search pending';
        state.classList.toggle('pending', !note.search_ready);
        card.querySelector('.summary-link span').textContent = note.summary
            ? 'Read summary'
            : 'Summarize';
        card.querySelector('.summary-link').setAttribute(
            'aria-label',
            'Summary for ' + note.title,
        );
        cards.append(card);
    }

    notesList.replaceChildren(cards);
}

function clearSearch() {
    clearTimeout(searchTimer);
    activeQuery = '';
    element('search-input').value = '';
    element('clear-search-button').hidden = true;
    loadNotes();
}

function searchNotes() {
    clearTimeout(searchTimer);
    activeQuery = element('search-input').value.trim();
    loadNotes();
}

function scheduleSearch() {
    clearTimeout(searchTimer);
    const query = element('search-input').value.trim();
    element('clear-search-button').hidden = query.length === 0;

    if (!query) {
        clearSearch();
        return;
    }

    // Stop an older response from replacing results while the user is typing.
    listRequestNumber++;
    searchTimer = setTimeout(searchNotes, 500);
}

function openEditor(note = null) {
    editingNoteId = note ? note.id : null;
    noteForm.reset();
    element('note-title').value = note ? note.title : '';
    element('note-content').value = note ? note.content : '';
    element('editor-heading').textContent = note
        ? 'A fresh perspective.'
        : 'A new thought.';
    element('content-count').textContent =
        `${element('note-content').value.length.toLocaleString()} / 12,000`;
    clearEditorErrors();
    editorDialog.showModal();
    element('note-title').focus();
}

function clearEditorErrors() {
    for (const field of ['title', 'content']) {
        showMessage(field + '-error', '');
        element('note-' + field).removeAttribute('aria-invalid');
    }

    showMessage('editor-error', '');
}

function setSaving(busy) {
    saving = busy;
    element('save-note-button').disabled = busy;
    element('save-note-button').querySelector('span').textContent = busy
        ? 'Saving and preparing…'
        : 'Save note';
    element('close-editor-button').disabled = busy;
    element('cancel-editor-button').disabled = busy;
    element('note-title').readOnly = busy;
    element('note-content').readOnly = busy;
}

async function saveNote(event) {
    event.preventDefault();

    if (saving) {
        return;
    }

    clearEditorErrors();
    setSaving(true);
    const path = editingNoteId ? '/api/notes/' + editingNoteId : '/api/notes';

    try {
        const result = await apiRequest(path, {
            method: editingNoteId ? 'PUT' : 'POST',
            body: JSON.stringify({
                title: element('note-title').value.trim(),
                content: element('note-content').value.trim(),
            }),
        });

        editorDialog.close();
        if (editingNoteId && selectedNote && selectedNote.id === result.data.id) {
            selectedNote = result.data;
            renderReader();
        }

        clearTimeout(searchTimer);
        activeQuery = '';
        element('search-input').value = '';
        element('clear-search-button').hidden = true;
        notify(result.warning || result.message, Boolean(result.warning));
        await loadNotes();
    } catch (error) {
        showMessage('editor-error', error.message);

        for (const field of ['title', 'content']) {
            if (error.errors && error.errors[field]) {
                showMessage(field + '-error', error.errors[field][0]);
                element('note-' + field).setAttribute('aria-invalid', 'true');
            }
        }
    } finally {
        setSaving(false);
    }
}

function renderReader() {
    const hasSummary = Boolean(selectedNote.summary);
    element('reader-title').textContent = selectedNote.title;
    element('reader-content').textContent = selectedNote.content;
    element('reader-date').textContent =
        'Updated ' +
        new Intl.DateTimeFormat('en', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        }).format(new Date(selectedNote.updated_at));
    element('summary-panel').classList.toggle('summary-empty', !hasSummary);
    element('summary-panel-heading').hidden = !hasSummary;
    element('reader-summary-title').textContent =
        selectedNote.summary_title || selectedNote.title;
    element('reader-summary').textContent = selectedNote.summary || '';
    element('reader-summary').hidden = !hasSummary;
    element('generate-summary-button').hidden = hasSummary;
    element('embedding-retry-panel').hidden = selectedNote.search_ready;
}

function noteIdFromUrl() {
    const match = window.location.pathname.match(/^\/notes\/(\d+)\/?$/);
    return match ? match[1] : null;
}

async function openNote(id, updateUrl = true) {
    const requestNumber = ++noteRequestNumber;

    try {
        const result = await apiRequest('/api/notes/' + id);

        if (requestNumber !== noteRequestNumber) {
            return;
        }

        selectedNote = result.data;
        showMessage('reader-error', '');
        renderReader();

        if (updateUrl && noteIdFromUrl() !== String(selectedNote.id)) {
            window.history.pushState(
                { noteReader: true },
                '',
                '/notes/' + selectedNote.id,
            );
        }

        if (!readerDialog.open) {
            readerDialog.showModal();
        }
    } catch (error) {
        if (requestNumber === noteRequestNumber) {
            notify(error.message, true);
        }
    }
}

function closeNote() {
    noteRequestNumber++;
    readerDialog.close();
    selectedNote = null;

    if (!noteIdFromUrl()) {
        return;
    }

    // A shared link has no list entry to go back to in this tab.
    if (window.history.state?.noteReader) {
        window.history.back();
    } else {
        window.history.replaceState(null, '', '/');
    }
}

function syncNoteWithUrl() {
    noteRequestNumber++;
    readerDialog.close();
    editorDialog.close();
    deleteDialog.close();
    selectedNote = null;

    const id = noteIdFromUrl();

    if (id) {
        openNote(id, false);
    }
}

async function processAi(action) {
    if (processingAi || !selectedNote) {
        return;
    }

    processingAi = true;
    const noteId = selectedNote.id;
    const button =
        action === 'summary'
            ? element('generate-summary-button')
            : element('retry-embedding-button');
    const label = button.querySelector('span') || button;
    const originalText = label.textContent;
    label.textContent =
        action === 'summary'
            ? 'Finding the key points…'
            : 'Preparing for search…';
    button.disabled = true;
    element('edit-note-button').disabled = true;
    element('delete-note-button').disabled = true;
    showMessage('reader-error', '');

    try {
        const result = await apiRequest(`/api/notes/${noteId}/${action}`, {
            method: 'POST',
        });

        if (selectedNote && selectedNote.id === noteId) {
            selectedNote = result.data;
            renderReader();
        }

        await loadNotes(currentPage);
    } catch (error) {
        if (selectedNote && selectedNote.id === noteId && readerDialog.open) {
            showMessage('reader-error', error.message);
        } else {
            notify(error.message, true);
        }
    } finally {
        label.textContent = originalText;
        button.disabled = false;
        element('edit-note-button').disabled = false;
        element('delete-note-button').disabled = false;
        processingAi = false;
    }
}

async function deleteNote() {
    if (deleting || !selectedNote) {
        return;
    }

    deleting = true;
    element('confirm-delete-button').disabled = true;
    element('confirm-delete-button').textContent = 'Deleting…';
    element('cancel-delete-button').disabled = true;
    showMessage('delete-error', '');

    try {
        await apiRequest('/api/notes/' + selectedNote.id, { method: 'DELETE' });
        deleteDialog.close();
        window.history.replaceState(null, '', '/');
        closeNote();
        notify('Note deleted.');
        await loadNotes(currentPage);
    } catch (error) {
        showMessage('delete-error', error.message);
    } finally {
        deleting = false;
        element('confirm-delete-button').disabled = false;
        element('confirm-delete-button').textContent = 'Delete note';
        element('cancel-delete-button').disabled = false;
    }
}

// Page and search controls.
element('new-note-button').addEventListener('click', () => openEditor());
element('empty-new-note-button').addEventListener('click', () => openEditor());
element('all-notes-button').addEventListener('click', clearSearch);
element('empty-clear-button').addEventListener('click', clearSearch);
element('clear-search-button').addEventListener('click', clearSearch);
element('focus-search-button').addEventListener('click', () =>
    element('search-input').focus(),
);
element('search-input').addEventListener('input', scheduleSearch);
element('search-form').addEventListener('submit', (event) => {
    event.preventDefault();
    searchNotes();
});
element('previous-page-button').addEventListener('click', () => {
    if (currentPage > 1) loadNotes(currentPage - 1);
});
element('next-page-button').addEventListener('click', () => {
    if (currentPage < lastPage) loadNotes(currentPage + 1);
});
notesList.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');

    if (button && !processingAi) {
        openNote(button.closest('.note-card').dataset.id);
    }
});

// Editor and reader controls.
noteForm.addEventListener('submit', saveNote);
element('close-editor-button').addEventListener('click', () =>
    editorDialog.close(),
);
element('cancel-editor-button').addEventListener('click', () =>
    editorDialog.close(),
);
editorDialog.addEventListener('cancel', (event) => {
    if (saving) event.preventDefault();
});
element('note-content').addEventListener('input', () => {
    element('content-count').textContent =
        `${element('note-content').value.length.toLocaleString()} / 12,000`;
});
element('close-reader-button').addEventListener('click', closeNote);
readerDialog.addEventListener('cancel', (event) => {
    event.preventDefault();
    closeNote();
});
element('generate-summary-button').addEventListener('click', () =>
    processAi('summary'),
);
element('retry-embedding-button').addEventListener('click', () =>
    processAi('embedding'),
);
element('edit-note-button').addEventListener('click', () => {
    openEditor(selectedNote);
});
element('delete-note-button').addEventListener('click', () => {
    element('delete-note-title').textContent = selectedNote.title;
    showMessage('delete-error', '');
    deleteDialog.showModal();
});
element('cancel-delete-button').addEventListener('click', () =>
    deleteDialog.close(),
);
element('confirm-delete-button').addEventListener('click', deleteNote);
deleteDialog.addEventListener('cancel', (event) => {
    if (deleting) event.preventDefault();
});
element('dismiss-notification-button').addEventListener(
    'click',
    () => (element('notification').hidden = true),
);

window.addEventListener('popstate', syncNoteWithUrl);

loadNotes();
syncNoteWithUrl();
