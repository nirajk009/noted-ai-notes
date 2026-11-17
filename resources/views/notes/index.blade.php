<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta
            name="description"
            content="Capture notes, search by meaning, and turn long thoughts into clear summaries."
        />
        <title>Noted — A little space for your thoughts</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <a class="skip-link" href="#main-content">Skip to notes</a>
        <aside class="sidebar">
            <a
                class="brand"
                href="{{ route('notes.index') }}"
                aria-label="Noted home"
            >
                <span class="brand-mark"><x-icon name="note" /></span>
                <span>
                    noted
                    <span class="brand-dot">.</span>
                </span>
            </a>
            <p class="brand-caption">A little space for big ideas.</p>
            <p class="nav-label">WORKSPACE</p>
            <nav class="navigation" aria-label="Workspace">
                <button
                    id="all-notes-button"
                    class="nav-button active"
                    type="button"
                    aria-label="All notes"
                    aria-current="page"
                >
                    <x-icon name="grid" />
                    <span>All notes</span>
                    <span id="sidebar-count" class="nav-count">0</span>
                </button>
                <button
                    id="focus-search-button"
                    class="nav-button"
                    type="button"
                    aria-label="Search notes"
                >
                    <x-icon name="search" />
                    <span>Search notes</span>
                </button>
            </nav>
            <div class="sidebar-tip">
                <span class="tip-icon"><x-icon name="sparkles" /></span>
                <h2>
                    Find the thought.
                    <br />
                    Not just the words.
                </h2>
                <p>
                    Describe what you remember. Search will find notes with a
                    similar meaning.
                </p>
            </div>
            <div class="sidebar-footer">
                <span class="status-dot"></span>
                A calmer place to think
            </div>
        </aside>

        <div class="workspace">
            <header class="topbar">
                <div class="breadcrumb">
                    <x-icon name="book" />
                    <span>Workspace</span>
                    <span class="breadcrumb-slash">/</span>
                    <strong>All notes</strong>
                </div>
                <span class="topbar-caption">Good ideas deserve a place.</span>
            </header>
            <main id="main-content" class="main-content">
                <section class="page-heading">
                    <div>
                        <p class="eyebrow">LESS CLUTTER. MORE CLARITY.</p>
                        <h1>
                            Your notes
                            <span>.</span>
                        </h1>
                        <p class="page-description">
                            Capture a thought. Make room for the next one.
                        </p>
                    </div>
                    <button
                        id="new-note-button"
                        class="button button-primary"
                        type="button"
                    >
                        <x-icon name="plus" />
                        New note
                    </button>
                </section>
                <section class="search-panel" aria-label="Search your notes">
                    <div class="search-heading">
                        <x-icon name="sparkles" />
                        <span>A thought away from what you need</span>
                        <span class="small-label">SEMANTIC SEARCH</span>
                    </div>
                    <form id="search-form" class="search-form" role="search">
                        <div class="search-input-wrap">
                            <x-icon name="search" />
                            <label class="sr-only" for="search-input">
                                Search notes by meaning
                            </label>
                            <input
                                id="search-input"
                                type="search"
                                maxlength="500"
                                placeholder="What are you looking for?"
                                autocomplete="off"
                            />
                            <button
                                id="clear-search-button"
                                class="icon-button"
                                type="button"
                                aria-label="Clear search"
                                hidden
                            >
                                <x-icon name="close" />
                            </button>
                        </div>
                        <button
                            id="search-button"
                            class="button button-search"
                            type="submit"
                        >
                            Search notes
                            <x-icon name="arrow" />
                        </button>
                    </form>
                    <p class="search-help">
                        Go beyond exact words. Try
                        <button id="search-example-button" type="button">
                            “ways to make an app faster”
                        </button>
                    </p>
                </section>
                <section class="notes-section" aria-labelledby="notes-heading">
                    <div class="section-toolbar">
                        <div class="section-title">
                            <h2 id="notes-heading">All notes</h2>
                            <span id="notes-count" class="count-badge">0</span>
                        </div>
                        <span id="sort-label" class="sort-label">
                            Recently updated
                        </span>
                    </div>
                    <div
                        id="list-error"
                        class="inline-message error-message"
                        role="alert"
                        hidden
                    ></div>
                    <p id="loading-state" class="loading-state" role="status">
                        <span class="spinner"></span>
                        Loading your notes…
                    </p>
                    <div
                        id="notes-list"
                        class="notes-grid"
                        aria-busy="true"
                    ></div>
                    <div id="empty-state" class="empty-state" hidden>
                        <div class="empty-illustration">
                            <span class="empty-line"></span>
                            <x-icon name="note" />
                            <span class="empty-spark">✦</span>
                        </div>
                        <h3 id="empty-heading">Your next idea starts here.</h3>
                        <p id="empty-description">
                            A quick thought, a useful discovery, a plan for
                            tomorrow. Give it a place to live.
                        </p>
                        <button
                            id="empty-new-note-button"
                            class="button button-primary"
                            type="button"
                        >
                            <x-icon name="plus" />
                            Write your first note
                        </button>
                        <button
                            id="empty-clear-button"
                            class="button button-secondary"
                            type="button"
                            hidden
                        >
                            Back to all notes
                        </button>
                    </div>
                    <div id="pagination" class="pagination" hidden>
                        <p id="pagination-description"></p>
                        <div class="pagination-controls">
                            <button
                                id="previous-page-button"
                                class="icon-button"
                                type="button"
                                aria-label="Previous page"
                            >
                                <x-icon name="chevron-left" />
                            </button>
                            <span id="page-label"></span>
                            <button
                                id="next-page-button"
                                class="icon-button"
                                type="button"
                                aria-label="Next page"
                            >
                                <x-icon name="chevron-right" />
                            </button>
                        </div>
                    </div>
                </section>
                <footer class="page-footer">
                    <span>YOUR WORDS. A LITTLE CLEARER.</span>
                    <span>
                        Made for the way you think.
                        <x-icon name="sparkles" />
                    </span>
                </footer>
            </main>
        </div>

        <template id="note-card-template">
            <article class="note-card">
                <button class="note-card-open" type="button" data-action="open">
                    <div class="note-card-top">
                        <span class="note-symbol"><x-icon name="note" /></span>
                        <time class="note-date"></time>
                    </div>
                    <h3 class="note-title"></h3>
                    <p class="note-excerpt"></p>
                </button>
                <div class="note-card-footer">
                    <span class="note-state"></span>
                    <button
                        class="summary-link"
                        type="button"
                        data-action="summary"
                    >
                        <x-icon name="sparkles" />
                        <span></span>
                    </button>
                </div>
            </article>
        </template>

        <dialog
            id="editor-dialog"
            class="dialog editor-dialog"
            aria-labelledby="editor-heading"
        >
            <form id="note-form">
                <div class="dialog-header">
                    <div>
                        <p class="eyebrow">MAKE A LITTLE ROOM</p>
                        <h2 id="editor-heading">A new thought.</h2>
                    </div>
                    <button
                        id="close-editor-button"
                        class="icon-button"
                        type="button"
                        aria-label="Close editor"
                    >
                        <x-icon name="close" />
                    </button>
                </div>
                <div class="dialog-body">
                    <label for="note-title">
                        Title
                        <span class="field-limit">255 characters max</span>
                    </label>
                    <input
                        id="note-title"
                        name="title"
                        type="text"
                        maxlength="255"
                        placeholder="Give your thought a title"
                        required
                        aria-describedby="title-error"
                    />
                    <p id="title-error" class="field-error" hidden></p>
                    <label for="note-content">
                        Your note
                        <span id="content-count" class="field-limit">
                            0 / 12,000
                        </span>
                    </label>
                    <textarea
                        id="note-content"
                        name="content"
                        rows="10"
                        maxlength="12000"
                        placeholder="Start with what’s on your mind…"
                        required
                        aria-describedby="content-error"
                    ></textarea>
                    <p id="content-error" class="field-error" hidden></p>
                    <p
                        id="editor-error"
                        class="inline-message error-message"
                        role="alert"
                        hidden
                    ></p>
                    <p class="editor-hint">
                        <x-icon name="check" />
                        Saved notes become searchable by meaning.
                    </p>
                </div>
                <div class="dialog-footer">
                    <button
                        id="cancel-editor-button"
                        class="button button-secondary"
                        type="button"
                    >
                        Cancel
                    </button>
                    <button
                        id="save-note-button"
                        class="button button-primary"
                        type="submit"
                    >
                        <x-icon name="check" />
                        <span>Save note</span>
                    </button>
                </div>
            </form>
        </dialog>

        <dialog
            id="reader-dialog"
            class="dialog reader-dialog"
            aria-labelledby="reader-title"
        >
            <div class="dialog-header">
                <span class="reader-label">
                    <x-icon name="note" />
                    YOUR NOTE
                </span>
                <button
                    id="close-reader-button"
                    class="icon-button"
                    type="button"
                    aria-label="Close note"
                >
                    <x-icon name="close" />
                </button>
            </div>
            <div class="dialog-body reader-body">
                <p id="reader-date" class="reader-date"></p>
                <h2 id="reader-title"></h2>
                <p id="reader-content" class="reader-content"></p>
                <section
                    class="summary-panel"
                    aria-labelledby="summary-heading"
                >
                    <div class="summary-panel-heading">
                        <h3 id="summary-heading">
                            <x-icon name="sparkles" />
                            A little clarity
                        </h3>
                        <span class="small-label">AI SUMMARY</span>
                    </div>
                    <p id="reader-summary">
                        Turn this note into a few clear sentences.
                    </p>
                    <button
                        id="generate-summary-button"
                        class="button button-secondary"
                        type="button"
                    >
                        <x-icon name="sparkles" />
                        <span>Summarize note</span>
                    </button>
                </section>
                <div
                    id="embedding-retry-panel"
                    class="embedding-retry-panel"
                    hidden
                >
                    <p>
                        This note is saved. Prepare it for semantic search when
                        you’re ready.
                    </p>
                    <button
                        id="retry-embedding-button"
                        class="button button-secondary"
                        type="button"
                    >
                        Prepare for search
                    </button>
                </div>
                <p
                    id="reader-error"
                    class="inline-message error-message"
                    role="alert"
                    hidden
                ></p>
            </div>
            <div class="dialog-footer reader-footer">
                <button
                    id="delete-note-button"
                    class="button button-danger"
                    type="button"
                >
                    <x-icon name="trash" />
                    Delete note
                </button>
                <button
                    id="edit-note-button"
                    class="button button-primary"
                    type="button"
                >
                    <x-icon name="edit" />
                    Edit note
                </button>
            </div>
        </dialog>

        <dialog
            id="delete-dialog"
            class="dialog delete-dialog"
            aria-labelledby="delete-heading"
        >
            <div class="dialog-body">
                <span class="delete-symbol"><x-icon name="trash" /></span>
                <h2 id="delete-heading">Let this note go?</h2>
                <p>
                    This will permanently delete
                    <strong id="delete-note-title"></strong>
                    from your workspace.
                </p>
                <p
                    id="delete-error"
                    class="inline-message error-message"
                    role="alert"
                    hidden
                ></p>
            </div>
            <div class="dialog-footer">
                <button
                    id="cancel-delete-button"
                    class="button button-secondary"
                    type="button"
                >
                    Keep note
                </button>
                <button
                    id="confirm-delete-button"
                    class="button button-danger-solid"
                    type="button"
                >
                    Delete note
                </button>
            </div>
        </dialog>
        <div
            id="notification"
            class="notification"
            role="status"
            aria-live="polite"
            hidden
        >
            <span id="notification-text"></span>
            <button
                id="dismiss-notification-button"
                class="icon-button"
                type="button"
                aria-label="Dismiss notification"
            >
                <x-icon name="close" />
            </button>
        </div>
    </body>
</html>
