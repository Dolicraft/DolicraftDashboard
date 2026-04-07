/**
 * DolicraftDashboard - Widget drag-and-drop and visibility management
 * Copyright (C) 2024-2026 Dolicraft
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
(function() {
    'use strict';

    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initDolicraftDashboard();
    });

    function initDolicraftDashboard() {
        // Find all widget zones (kpi-zone and table-zone)
        var zones = document.querySelectorAll('.dolicraft-widget-zone');
        if (!zones.length) return;

        zones.forEach(function(zone) {
            initDragAndDrop(zone);
        });

        // Init toggle buttons
        document.querySelectorAll('.dolicraft-widget-toggle').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var widget = this.closest('.dolicraft-widget');
                if (!widget) return;
                var key = widget.getAttribute('data-widget');
                var isVisible = !widget.classList.contains('dolicraft-widget-hidden');
                toggleWidget(key, !isVisible, widget, this);
            });
        });

        // Reset button
        var resetBtn = document.getElementById('dolicraft-reset-layout');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                resetLayout();
            });
        }
    }

    var draggedElement = null;
    var dragPlaceholder = null;

    function initDragAndDrop(zone) {
        var widgets = zone.querySelectorAll('.dolicraft-widget');

        widgets.forEach(function(widget) {
            // Only drag from the handle
            var handle = widget.querySelector('.dolicraft-drag-handle');
            if (!handle) return;

            handle.addEventListener('mousedown', function() {
                widget.setAttribute('draggable', 'true');
            });

            widget.addEventListener('dragstart', function(e) {
                draggedElement = this;
                this.classList.add('dolicraft-widget-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', this.getAttribute('data-widget'));

                // Create placeholder
                dragPlaceholder = document.createElement('div');
                dragPlaceholder.className = 'dolicraft-drop-placeholder';
                dragPlaceholder.style.height = this.offsetHeight + 'px';
            });

            widget.addEventListener('dragend', function() {
                this.classList.remove('dolicraft-widget-dragging');
                this.removeAttribute('draggable');
                draggedElement = null;
                if (dragPlaceholder && dragPlaceholder.parentNode) {
                    dragPlaceholder.parentNode.removeChild(dragPlaceholder);
                }
                dragPlaceholder = null;
                // Remove all drag-over classes
                zone.querySelectorAll('.dolicraft-widget-dragover').forEach(function(el) {
                    el.classList.remove('dolicraft-widget-dragover');
                });
                // Save new order
                saveOrder(zone);
            });

            widget.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (!draggedElement || draggedElement === this) return;

                var rect = this.getBoundingClientRect();
                var midY = rect.top + rect.height / 2;
                var parent = this.parentNode;

                if (e.clientY < midY) {
                    parent.insertBefore(dragPlaceholder, this);
                } else {
                    parent.insertBefore(dragPlaceholder, this.nextSibling);
                }
            });

            widget.addEventListener('drop', function(e) {
                e.preventDefault();
                if (!draggedElement) return;
                // Insert dragged element where placeholder is
                if (dragPlaceholder && dragPlaceholder.parentNode) {
                    dragPlaceholder.parentNode.insertBefore(draggedElement, dragPlaceholder);
                    dragPlaceholder.parentNode.removeChild(dragPlaceholder);
                }
            });
        });

        // Zone-level dragover to handle empty zones
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
        });

        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggedElement && dragPlaceholder && dragPlaceholder.parentNode) {
                dragPlaceholder.parentNode.insertBefore(draggedElement, dragPlaceholder);
                dragPlaceholder.parentNode.removeChild(dragPlaceholder);
            }
        });
    }

    function saveOrder(zone) {
        // Collect order from all zones on the page
        var allWidgets = document.querySelectorAll('.dolicraft-widget');
        var order = [];
        var pos = 0;
        allWidgets.forEach(function(w) {
            order.push({
                key: w.getAttribute('data-widget'),
                position: pos,
                visible: w.classList.contains('dolicraft-widget-hidden') ? 0 : 1
            });
            pos++;
        });

        // Get ajax URL from data attribute on container
        var container = document.getElementById('dolicraft-dashboard-container');
        if (!container) return;
        var ajaxUrl = container.getAttribute('data-ajax-url');
        var token = container.getAttribute('data-token');

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=save_order&token=' + encodeURIComponent(token) + '&widgets=' + encodeURIComponent(JSON.stringify(order))
        }).catch(function(err) {
            console.error('DolicraftDashboard: failed to save order', err);
        });
    }

    function toggleWidget(key, visible, widgetEl, btnEl) {
        if (visible) {
            widgetEl.classList.remove('dolicraft-widget-hidden');
            widgetEl.style.display = '';
            btnEl.innerHTML = '<span class="fas fa-eye"></span>';
            btnEl.title = 'Hide';
        } else {
            widgetEl.classList.add('dolicraft-widget-hidden');
            widgetEl.style.display = 'none';
            btnEl.innerHTML = '<span class="fas fa-eye-slash"></span>';
            btnEl.title = 'Show';
        }

        var container = document.getElementById('dolicraft-dashboard-container');
        if (!container) return;
        var ajaxUrl = container.getAttribute('data-ajax-url');
        var token = container.getAttribute('data-token');

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=toggle_widget&token=' + encodeURIComponent(token) + '&widget_key=' + encodeURIComponent(key) + '&visible=' + (visible ? 1 : 0)
        }).catch(function(err) {
            console.error('DolicraftDashboard: failed to toggle widget', err);
        });
    }

    function resetLayout() {
        var container = document.getElementById('dolicraft-dashboard-container');
        if (!container) return;
        var ajaxUrl = container.getAttribute('data-ajax-url');
        var token = container.getAttribute('data-token');

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=reset&token=' + encodeURIComponent(token)
        }).then(function() {
            window.location.reload();
        }).catch(function(err) {
            console.error('DolicraftDashboard: failed to reset layout', err);
        });
    }
})();
