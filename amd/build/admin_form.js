// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Admin form functionality for timeline module.
 *
 * @module     mod_timeline/admin_form
 * @copyright  2025 Raccoon Dev
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    'use strict';

    return {
        init: function() {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    this.initEventDeletion();
                    this.initScrollPreservation();
                });
            } else {
                this.initEventDeletion();
                this.initScrollPreservation();
            }
        },

        initEventDeletion: function() {
            const titleInputs = document.querySelectorAll('input[name^="eventtitle"]');

            if (titleInputs.length === 0) {
                return;
            }

            titleInputs.forEach((titleInput) => {
                const match = titleInput.name.match(/\[(\d+)\]/);
                if (!match) {
                    return;
                }

                const repeatIndex = match[1];
                const contentTypeSelect = document.querySelector(`select[name='eventcontenttype[${repeatIndex}]']`);
                const deletedInput = document.querySelector(`input[name='eventdeleted[${repeatIndex}]']`);

                if (!titleInput || !deletedInput || !contentTypeSelect) {
                    return;
                }

                const contentTypeFitem = contentTypeSelect.closest('.fitem');
                if (!contentTypeFitem) {
                    return;
                }

                // Add HR separator before this event (except for the first event)
                if (parseInt(repeatIndex) > 0) {
                    const existingHr = contentTypeFitem.previousElementSibling;
                    if (!existingHr || !existingHr.classList.contains('event-separator-hr')) {
                        const hr = document.createElement('hr');
                        hr.className = 'event-separator-hr';
                        hr.style.cssText = 'border: 0; border-top: 3px solid #e3e6ec; margin: 20px 0;';
                        contentTypeFitem.parentNode.insertBefore(hr, contentTypeFitem);
                    }
                }

                // Create remove button
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-danger btn-sm';
                const eventTitle = titleInput.value.trim() || 'Event ' + (parseInt(repeatIndex) + 1);
                btn.textContent = 'Remove: ' + eventTitle;
                btn.style.marginTop = '15px';
                btn.style.marginBottom = '0';
                btn.style.display = 'block';
                btn.title = 'Delete this event';

                btn.addEventListener('click', (e) => {
                    e.preventDefault();

                    if (!confirm('Are you sure you want to remove this event?')) {
                        return;
                    }

                    // Hide all fitems for this event by ID pattern
                    const fieldPrefixes = ['eventdate', 'eventtitle', 'eventshortdesc', 'eventdescription', 'eventcontenttype'];

                    fieldPrefixes.forEach((prefix) => {
                        const fitemId = 'fitem_id_' + prefix + '_' + repeatIndex;
                        const fitem = document.getElementById(fitemId);
                        if (fitem) {
                            fitem.style.display = 'none';
                        }
                    });

                    // Set eventdeleted flag
                    deletedInput.value = '1';
                });

                // Insert button in the felement of contenttype field
                const felement = contentTypeFitem.querySelector('.felement');
                if (felement) {
                    const colMd9 = felement.closest('.col-md-9');
                    if (colMd9) {
                        colMd9.style.display = 'flex';
                        colMd9.style.flexDirection = 'column';
                        colMd9.style.justifyContent = 'space-between';
                    }
                    felement.appendChild(btn);
                }
            });
        },

        initScrollPreservation: function() {
            const form = document.querySelector('form.mform');
            if (form) {
                form.addEventListener('submit', (e) => {
                    if (e.submitter && e.submitter.name === 'addevent') {
                        sessionStorage.setItem('timeline_scroll_to_last', 'true');
                    }
                });
            }

            if (sessionStorage.getItem('timeline_scroll_to_last') === 'true') {
                sessionStorage.removeItem('timeline_scroll_to_last');

                setTimeout(() => {
                    const titleInputs = document.querySelectorAll('input[name^="eventtitle"]');
                    if (titleInputs.length > 0) {
                        const lastInput = titleInputs[titleInputs.length - 1];
                        lastInput.scrollIntoView({behavior: 'smooth', block: 'center'});
                        lastInput.focus();
                    }
                }, 500);
            }
        }
    };
});
