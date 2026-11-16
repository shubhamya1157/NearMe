// frontend/js/hobbies.js
// Handles dynamic hobby selection, custom hobby tags, and form input synchronization

document.addEventListener('DOMContentLoaded', function () {
    const hiddenInput = document.getElementById('hobbiesHiddenInput');
    const selectedContainer = document.getElementById('selectedHobbiesContainer');
    const customHobbyInput = document.getElementById('customHobbyInput');
    const btnAddHobby = document.getElementById('btnAddCustomHobby');
    const presetChips = document.querySelectorAll('.chip-choice');

    let hobbiesList = [];

    // Initialize hobbies from hidden input if pre-filled
    if (hiddenInput && hiddenInput.value.trim() !== '') {
        const raw = hiddenInput.value.split(',');
        raw.forEach(item => {
            const trimmed = item.trim();
            if (trimmed !== '' && !hobbiesList.some(h => h.toLowerCase() === trimmed.toLowerCase())) {
                hobbiesList.push(trimmed);
            }
        });
    }

    function syncHiddenInput() {
        if (hiddenInput) {
            hiddenInput.value = hobbiesList.join(', ');
        }
    }

    function updatePresetHighlight() {
        presetChips.forEach(chip => {
            const hobbyName = chip.getAttribute('data-hobby');
            if (hobbiesList.some(h => h.toLowerCase() === hobbyName.toLowerCase())) {
                chip.classList.add('active');
            } else {
                chip.classList.remove('active');
            }
        });
    }

    function renderHobbies() {
        if (!selectedContainer) return;

        selectedContainer.innerHTML = '';

        if (hobbiesList.length === 0) {
            selectedContainer.innerHTML = '<span style="color: #94a3b8; font-size: 0.88rem; font-style: italic; align-self: center; padding: 0.3rem;">No hobbies selected yet. Click options below or type your own!</span>';
            syncHiddenInput();
            updatePresetHighlight();
            return;
        }

        hobbiesList.forEach((hobby, index) => {
            const tag = document.createElement('span');
            tag.className = 'selected-tag';
            tag.innerHTML = `
                <span>${escapeHtml(hobby)}</span>
                <button type="button" class="remove-tag-btn" data-index="${index}" title="Remove hobby">&times;</button>
            `;
            selectedContainer.appendChild(tag);
        });

        // Add remove handlers
        selectedContainer.querySelectorAll('.remove-tag-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.getAttribute('data-index'), 10);
                removeHobby(idx);
            });
        });

        syncHiddenInput();
        updatePresetHighlight();
    }

    function addHobby(name) {
        const trimmed = name.trim();
        if (trimmed === '') return;

        // Check if hobby already added (case-insensitive)
        const exists = hobbiesList.some(h => h.toLowerCase() === trimmed.toLowerCase());
        if (!exists) {
            // Capitalize first letter of each word nicely
            const formatted = trimmed.replace(/\b\w/g, l => l.toUpperCase());
            hobbiesList.push(formatted);
            renderHobbies();
        }
    }

    function removeHobby(index) {
        if (index >= 0 && index < hobbiesList.length) {
            hobbiesList.splice(index, 1);
            renderHobbies();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Preset chip clicks
    presetChips.forEach(chip => {
        chip.addEventListener('click', function () {
            const hobby = this.getAttribute('data-hobby');
            const existingIdx = hobbiesList.findIndex(h => h.toLowerCase() === hobby.toLowerCase());
            if (existingIdx !== -1) {
                removeHobby(existingIdx);
            } else {
                addHobby(hobby);
            }
        });
    });

    // Custom hobby add button
    if (btnAddHobby && customHobbyInput) {
        btnAddHobby.addEventListener('click', function () {
            addHobby(customHobbyInput.value);
            customHobbyInput.value = '';
            customHobbyInput.focus();
        });

        customHobbyInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addHobby(customHobbyInput.value);
                customHobbyInput.value = '';
            }
        });
    }

    // Initial render
    renderHobbies();
});
