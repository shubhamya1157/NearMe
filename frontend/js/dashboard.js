// frontend/js/dashboard.js
// Handles client-side live filtering, sorting, distance slider, and detail modals

document.addEventListener('DOMContentLoaded', function () {
    const radiusSlider = document.getElementById('radiusSlider');
    const radiusBadge = document.getElementById('radiusBadge');
    const hobbySelect = document.getElementById('hobbySelect');
    const minMatchSelect = document.getElementById('minMatchSelect');
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');
    const btnReset = document.getElementById('btnResetFilters');
    const peopleGrid = document.getElementById('peopleGrid');
    const emptyState = document.getElementById('emptyState');
    const statTotal = document.getElementById('statTotalFound');
    const statMatches = document.getElementById('statMatchesCount');
    const statClosest = document.getElementById('statClosestDist');

    // Detail Modal Elements
    const modalOverlay = document.getElementById('userDetailModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalAvatar = document.getElementById('modalAvatar');
    const modalName = document.getElementById('modalName');
    const modalHandle = document.getElementById('modalHandle');
    const modalDistance = document.getElementById('modalDistance');
    const modalPhone = document.getElementById('modalPhone');
    const modalPhoneBtn = document.getElementById('modalPhoneBtn');
    const modalEmail = document.getElementById('modalEmail');
    const modalAddress = document.getElementById('modalAddress');
    const modalMatchedHobbies = document.getElementById('modalMatchedHobbies');
    const modalAllHobbies = document.getElementById('modalAllHobbies');

    let debounceTimer = null;

    // Helper: Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Helper: Generate initials
    function getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    // Avatar gradient classes array
    const gradients = ['avatar-gradient-1', 'avatar-gradient-2', 'avatar-gradient-3', 'avatar-gradient-4', 'avatar-gradient-5'];
    function getAvatarGradient(id) {
        return gradients[(id || 0) % gradients.length];
    }

    // Update radius badge label
    function updateRadiusBadge() {
        if (!radiusSlider || !radiusBadge) return;
        const val = parseInt(radiusSlider.value, 10);
        if (val >= 100) {
            radiusBadge.textContent = 'Any Distance';
        } else {
            radiusBadge.textContent = `Within ${val} km`;
        }
    }

    // Fetch and re-render nearby people
    function fetchPeople() {
        if (!peopleGrid) return;

        const radiusVal = radiusSlider ? parseInt(radiusSlider.value, 10) : 100;
        const radius = radiusVal >= 100 ? '' : radiusVal;
        const hobby = hobbySelect ? hobbySelect.value : '';
        const minMatch = minMatchSelect ? minMatchSelect.value : '0';
        const search = searchInput ? searchInput.value.trim() : '';
        const sort = sortSelect ? sortSelect.value : 'distance';

        const params = new URLSearchParams();
        if (radius !== '') params.append('radius', radius);
        if (hobby !== '') params.append('hobby', hobby);
        if (minMatch !== '0') params.append('min_match', minMatch);
        if (search !== '') params.append('search', search);
        if (sort !== '') params.append('sort', sort);

        // Visual loading feedback
        peopleGrid.style.opacity = '0.5';

        fetch(`../backend/find_people.php?${params.toString()}`)
            .then(res => res.json())
            .then(resData => {
                peopleGrid.style.opacity = '1';
                if (resData.status === 'success') {
                    renderCards(resData.data);
                    updateStats(resData.data);
                }
            })
            .catch(err => {
                peopleGrid.style.opacity = '1';
                console.error('Error fetching people:', err);
            });
    }

    // Render cards into grid
    function renderCards(people) {
        if (!peopleGrid) return;
        peopleGrid.innerHTML = '';

        if (!people || people.length === 0) {
            if (emptyState) emptyState.style.display = 'block';
            return;
        }

        if (emptyState) emptyState.style.display = 'none';

        people.forEach(person => {
            const card = document.createElement('div');
            const hasMatch = person.match_count > 0;
            card.className = `user-card ${hasMatch ? 'has-match' : ''}`;

            const initials = getInitials(person.name);
            const gradClass = getAvatarGradient(person.id);
            const distText = person.distance_km !== null ? `${person.distance_km} km away` : 'Location not set';
            const isClose = person.distance_km !== null && person.distance_km <= 5;

            // Matched hobbies chips HTML
            let matchedHtml = '';
            if (person.matched_hobbies && person.matched_hobbies.length > 0) {
                matchedHtml = person.matched_hobbies.map(h => 
                    `<span class="tag tag-matched">🎯 ${escapeHtml(h)}</span>`
                ).join(' ');
            }

            // Unmatched hobbies chips HTML
            let unmatchedHtml = '';
            if (person.unmatched_hobbies && person.unmatched_hobbies.length > 0) {
                unmatchedHtml = person.unmatched_hobbies.map(h => 
                    `<span class="tag tag-unmatched">${escapeHtml(h)}</span>`
                ).join(' ');
            }

            card.innerHTML = `
                <div>
                    <div class="card-top">
                        <div class="card-user-info">
                            <div class="avatar-circle avatar-circle-sm ${gradClass}">
                                ${initials}
                            </div>
                            <div class="card-names">
                                <h4>${escapeHtml(person.name)}</h4>
                                <p class="card-handle">@${escapeHtml(person.username)}</p>
                            </div>
                        </div>
                        <span class="distance-badge ${isClose ? 'close' : ''}">
                            📍 ${distText}
                        </span>
                    </div>

                    ${person.address ? `
                        <div class="card-address">
                            <span>🏠</span>
                            <span>${escapeHtml(person.address)}</span>
                        </div>
                    ` : ''}

                    ${hasMatch ? `
                        <div class="card-match-banner">
                            ✨ <strong>${person.match_count}</strong> hobby ${person.match_count === 1 ? 'match' : 'matches'} in common!
                        </div>
                    ` : ''}

                    <div class="card-hobbies-section">
                        <span class="section-label">Hobbies & Interests</span>
                        <div class="hobbies-tags-wrap">
                            ${matchedHtml}
                            ${unmatchedHtml}
                            ${!matchedHtml && !unmatchedHtml ? '<span style="color: #94a3b8; font-size: 0.8rem;">No hobbies listed</span>' : ''}
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <span style="font-size: 0.82rem; color: #64748b;">
                        ${person.phone ? `📞 ${escapeHtml(person.phone)}` : 'No phone listed'}
                    </span>
                    <button type="button" class="btn btn-secondary btn-sm btn-view-person" data-person='${JSON.stringify(person).replace(/'/g, "&apos;")}'>
                        View Details
                    </button>
                </div>
            `;

            peopleGrid.appendChild(card);
        });

        // Attach modal trigger listeners
        document.querySelectorAll('.btn-view-person').forEach(btn => {
            btn.addEventListener('click', function () {
                const personData = JSON.parse(this.getAttribute('data-person'));
                openModal(personData);
            });
        });
    }

    // Update summary stats
    function updateStats(people) {
        if (!people) return;

        if (statTotal) statTotal.textContent = people.length;

        const matchCount = people.filter(p => p.match_count > 0).length;
        if (statMatches) statMatches.textContent = matchCount;

        if (statClosest) {
            const withDist = people.filter(p => p.distance_km !== null);
            if (withDist.length > 0) {
                const min = Math.min(...withDist.map(p => p.distance_km));
                statClosest.textContent = `${min} km`;
            } else {
                statClosest.textContent = '—';
            }
        }
    }

    // Modal display
    function openModal(person) {
        if (!modalOverlay) return;

        if (modalAvatar) {
            modalAvatar.textContent = getInitials(person.name);
            modalAvatar.className = `avatar-circle ${getAvatarGradient(person.id)}`;
        }
        if (modalName) modalName.textContent = person.name;
        if (modalHandle) modalHandle.textContent = `@${person.username}`;
        if (modalDistance) {
            modalDistance.textContent = person.distance_km !== null ? `📍 ${person.distance_km} km away from your location` : '📍 Distance not available';
        }
        if (modalPhone) {
            modalPhone.textContent = person.phone || 'Not provided';
        }
        if (modalPhoneBtn) {
            if (person.phone) {
                modalPhoneBtn.href = `tel:${person.phone}`;
                modalPhoneBtn.style.display = 'inline-flex';
            } else {
                modalPhoneBtn.style.display = 'none';
            }
        }
        if (modalEmail) modalEmail.textContent = person.email;
        if (modalAddress) modalAddress.textContent = person.address || 'Not provided';

        // Matched hobbies
        if (modalMatchedHobbies) {
            modalMatchedHobbies.innerHTML = '';
            if (person.matched_hobbies && person.matched_hobbies.length > 0) {
                person.matched_hobbies.forEach(h => {
                    const tag = document.createElement('span');
                    tag.className = 'tag tag-matched';
                    tag.textContent = `🎯 ${h}`;
                    modalMatchedHobbies.appendChild(tag);
                });
            } else {
                modalMatchedHobbies.innerHTML = '<span style="color:#94a3b8; font-size:0.85rem;">No hobbies in common</span>';
            }
        }

        // All hobbies
        if (modalAllHobbies) {
            modalAllHobbies.innerHTML = '';
            if (person.hobbies_list && person.hobbies_list.length > 0) {
                person.hobbies_list.forEach(h => {
                    const tag = document.createElement('span');
                    tag.className = 'tag tag-unmatched';
                    tag.textContent = h;
                    modalAllHobbies.appendChild(tag);
                });
            } else {
                modalAllHobbies.innerHTML = '<span style="color:#94a3b8; font-size:0.85rem;">No hobbies listed</span>';
            }
        }

        modalOverlay.classList.add('active');
    }

    function closeModal() {
        if (modalOverlay) {
            modalOverlay.classList.remove('active');
        }
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', function (e) {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    }

    // Debounced filter trigger
    function triggerFilter() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchPeople, 250);
    }

    // Event listeners for filters
    if (radiusSlider) {
        radiusSlider.addEventListener('input', function () {
            updateRadiusBadge();
            triggerFilter();
        });
    }

    if (hobbySelect) {
        hobbySelect.addEventListener('change', triggerFilter);
    }

    if (minMatchSelect) {
        minMatchSelect.addEventListener('change', triggerFilter);
    }

    if (searchInput) {
        searchInput.addEventListener('input', triggerFilter);
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', triggerFilter);
    }

    if (btnReset) {
        btnReset.addEventListener('click', function () {
            if (radiusSlider) {
                radiusSlider.value = 100;
                updateRadiusBadge();
            }
            if (hobbySelect) hobbySelect.value = '';
            if (minMatchSelect) minMatchSelect.value = '0';
            if (searchInput) searchInput.value = '';
            if (sortSelect) sortSelect.value = 'distance';
            fetchPeople();
        });
    }

    // Initialize radius badge & attach listeners to initial server-rendered cards
    updateRadiusBadge();
    document.querySelectorAll('.btn-view-person').forEach(btn => {
        btn.addEventListener('click', function () {
            const personData = JSON.parse(this.getAttribute('data-person'));
            openModal(personData);
        });
    });
});
