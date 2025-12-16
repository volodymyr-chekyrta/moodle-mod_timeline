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
 * Interactive Timeline JavaScript functionality.
 *
 * @package    mod_timeline
 * @copyright  2025 Raccoon Dev
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function() {
    'use strict';

    function showPopup(event, openLinkText) {
        // Create modal backdrop
        var backdrop = document.createElement('div');
        backdrop.className = 'timeline-modal-backdrop';
        backdrop.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9998;display:flex;align-items:center;justify-content:center;';
        
        // Create modal content
        var modal = document.createElement('div');
        modal.className = 'timeline-modal';
        modal.style.cssText = 'background:#fff;border-radius:8px;padding:30px;max-width:800px;width:90%;max-height:80vh;overflow-y:auto;position:relative;z-index:9999;box-shadow:0 10px 40px rgba(0,0,0,0.3);';
        
        var html = '<button class="timeline-modal-close" style="position:absolute;top:15px;right:15px;background:transparent;border:none;font-size:28px;cursor:pointer;color:#666;line-height:1;">&times;</button>';
        
        if (event.datestring) {
            html += '<div class="timeline-detail__date" style="color:#666;margin-bottom:10px;">' + event.datestring + '</div>';
        }
        if (event.title) {
            html += '<h3 class="timeline-detail__title" style="margin-top:0;margin-bottom:15px;font-size:24px;">' + event.title + '</h3>';
        }
        if (event.hasdescription && event.description) {
            html += '<div class="timeline-detail__description" style="margin-bottom:15px;">' + event.description + '</div>';
        }
        
        modal.innerHTML = html;
        backdrop.appendChild(modal);
        document.body.appendChild(backdrop);
        
        // Close on backdrop click
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop || e.target.classList.contains('timeline-modal-close')) {
                document.body.removeChild(backdrop);
            }
        });
        
        // Close on Escape key
        var escapeHandler = function(e) {
            if (e.key === 'Escape') {
                if (document.body.contains(backdrop)) {
                    document.body.removeChild(backdrop);
                }
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }

    function renderDetail(container, event, openLinkText) {
        var detail = container.querySelector('.timeline-detail');
        if (!detail || !event) {
            return;
        }

        var html = '';
        if (event.datestring) {
            html += '<div class="timeline-detail__date">' + event.datestring + '</div>';
        }
        if (event.title) {
            html += '<h3 class="timeline-detail__title">' + event.title + '</h3>';
        }
        if (event.hasdescription && event.description) {
            html += '<div class="timeline-detail__description">' + event.description + '</div>';
        }
        if (!html) {
            html = '<div class="timeline-detail__empty">' + (event.message || '') + '</div>';
        }

        detail.innerHTML = html;
    }

    function activateEvent(buttons, activeIndex) {
        buttons.forEach(function(button, idx) {
            var isActive = idx === activeIndex;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function addTimelineMarkers(container, events) {
        var track = container.querySelector('.mod-timeline__track');
        if (!track || !events.length) {
            return;
        }

        var lastYear = null;
        var years = [];
        var yearPositions = {};
        
        // Update timeline line width based on actual track width
        var trackWidth = track.scrollWidth;
        var lineEl = document.createElement('div');
        lineEl.className = 'timeline-main-line';
        lineEl.style.cssText = 'position:absolute;top:20px;left:0;width:' + trackWidth + 'px;height:3px;background:linear-gradient(to right, #e5e7eb 0%, #3b82f6 50%, #e5e7eb 100%);border-radius:2px;z-index:0;pointer-events:none;';
        
        // Remove old line if exists
        var oldLine = track.querySelector('.timeline-main-line');
        if (oldLine) oldLine.remove();
        
        track.insertBefore(lineEl, track.firstChild);
        
        events.forEach(function(event, index) {
            if (!event.timestamp) return;
            
            var button = track.querySelector('.timeline-event[data-index="' + index + '"]');
            if (!button) return;
            
            var date = new Date(event.timestamp * 1000);
            var year = date.getFullYear();
            var day = date.getDate();
            var month = date.toLocaleDateString('en-US', { month: 'short' });
            
            // Track years and their positions
            if (!yearPositions[year]) {
                years.push(year);
                yearPositions[year] = button.offsetLeft;
            }
            
            // Add short date marker
            var dateMarker = document.createElement('div');
            dateMarker.className = 'timeline-date-marker';
            dateMarker.textContent = day + ' ' + month;
            dateMarker.style.left = (button.offsetLeft + button.offsetWidth / 2) + 'px';
            dateMarker.style.transform = 'translateX(-50%)';
            track.appendChild(dateMarker);
            
            // Add year separator when year changes
            if (lastYear !== null && lastYear !== year) {
                var separator = document.createElement('div');
                separator.className = 'timeline-year-separator';
                separator.setAttribute('data-year', year);
                
                // Position between previous event and current event
                var prevButton = track.querySelector('.timeline-event[data-index="' + (index - 1) + '"]');
                if (prevButton) {
                    var leftPos = prevButton.offsetLeft + prevButton.offsetWidth + 
                                 ((button.offsetLeft - (prevButton.offsetLeft + prevButton.offsetWidth)) / 2);
                    separator.style.left = leftPos + 'px';
                    track.appendChild(separator);
                }
            }
            
            lastYear = year;
        });
        
        // Add year navigation if multiple years
        if (years.length > 1) {
            // Add min year marker at the start (CSS styling will match year-separator)
            var minYear = Math.min.apply(Math, years);
            var minYearMarker = document.createElement('div');
            minYearMarker.className = 'timeline-min-year-marker';
            minYearMarker.textContent = minYear;
            track.appendChild(minYearMarker);
            
            addYearNavigation(container, years, yearPositions);
        }
    }
    
    function addYearNavigation(container, years, yearPositions) {
        var scroll = container.querySelector('.mod-timeline__scroll');
        var track = container.querySelector('.mod-timeline__track');
        if (!scroll || !track) return;
        
        // Check if horizontal scroll exists
        var hasScroll = track.scrollWidth > scroll.clientWidth;
        if (!hasScroll) {
            return; // Don't show navigation if no scroll needed
        }
        
        var nav = document.createElement('div');
        nav.className = 'timeline-year-nav';
        nav.style.cssText = 'display:flex;gap:8px;margin-bottom:15px;flex-wrap:wrap;';
        
        years.forEach(function(year) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-primary';
            btn.textContent = year;
            btn.style.cssText = 'padding:4px 12px;font-size:0.85rem;font-weight:600;';
            
            btn.addEventListener('click', function() {
                var targetLeft = yearPositions[year];
                
                // On mobile/responsive, scroll so year appears on the left side
                // On desktop, center or show with some offset
                var viewportWidth = scroll.clientWidth;
                var isMobile = viewportWidth < 768;
                
                var scrollOffset;
                if (isMobile) {
                    // On mobile: position year at ~20% from left edge for visibility
                    scrollOffset = Math.max(0, targetLeft - (viewportWidth * 0.2));
                } else {
                    // On desktop: small offset to show year separator comfortably
                    scrollOffset = Math.max(0, targetLeft - 150);
                }
                
                scroll.scrollTo({
                    left: scrollOffset,
                    behavior: 'smooth'
                });
            });
            
            nav.appendChild(btn);
        });
        
        scroll.parentElement.insertBefore(nav, scroll);
        
        // Recheck on window resize
        var resizeHandler = function() {
            var hasScrollNow = track.scrollWidth > scroll.clientWidth;
            nav.style.display = hasScrollNow ? 'flex' : 'none';
        };
        window.addEventListener('resize', resizeHandler);
    }

    function initTimeline(container) {
        var dataEl = container.querySelector('.mod-timeline__data');
        if (!dataEl) {
            return;
        }

        var events = [];
        try {
            events = JSON.parse(dataEl.textContent || '[]') || [];
        } catch (e) {
            events = [];
        }

        var buttons = Array.prototype.slice.call(container.querySelectorAll('.timeline-event'));
        if (!events.length || !buttons.length) {
            return;
        }

        var openLinkText = container.dataset.openlink || '';
        
        // Add timeline markers after buttons are rendered
        setTimeout(function() {
            addTimelineMarkers(container, events);
        }, 100);
        
        // Re-add markers on window resize
        var resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                // Clear old markers
                var oldMarkers = container.querySelectorAll('.timeline-date-marker, .timeline-year-separator, .timeline-year-nav');
                oldMarkers.forEach(function(m) { m.remove(); });
                // Re-add markers
                addTimelineMarkers(container, events);
            }, 250);
        });

        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                var index = parseInt(button.getAttribute('data-index'), 10);
                if (isNaN(index) || !events[index]) {
                    return;
                }
                var event = events[index];
                
                // Always activate the clicked button
                activateEvent(buttons, index);
                
                // Check if this event should open in popup
                if (event.ispopup) {
                    // Hide the detail panel for popup events
                    var detail = container.querySelector('.timeline-detail');
                    if (detail) {
                        detail.style.display = 'none';
                    }
                    showPopup(event, openLinkText);
                } else {
                    // Show the detail panel for tab events
                    var detail = container.querySelector('.timeline-detail');
                    if (detail) {
                        detail.style.display = '';
                    }
                    renderDetail(container, events[index], openLinkText);
                    
                    // Scroll to detail section smoothly with offset for header
                    setTimeout(function() {
                        var detailRect = detail.getBoundingClientRect();
                        var headerHeight = 60; // Approximate header height
                        var offset = 20; // Additional offset
                        var scrollTop = window.pageYOffset + detailRect.top - headerHeight - offset;
                        
                        window.scrollTo({
                            top: scrollTop,
                            behavior: 'smooth'
                        });
                    }, 100);
                }
            });
        });

        // Initialize first event
        activateEvent(buttons, 0);
        if (events[0] && events[0].ispopup) {
            // If first event is popup, hide detail panel
            var detail = container.querySelector('.timeline-detail');
            if (detail) {
                detail.style.display = 'none';
            }
        } else {
            renderDetail(container, events[0], openLinkText);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var timelines = document.querySelectorAll('.mod-timeline');
        timelines.forEach(initTimeline);
    });
})();
