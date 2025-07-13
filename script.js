document.addEventListener('DOMContentLoaded', function () {
    // مدیریت کلیک روی سریال‌ها
    const seriesButtons = document.querySelectorAll('.toggle-series');
    seriesButtons.forEach(button => {
        button.addEventListener('click', function () {
            const seriesId = this.getAttribute('data-series-id');
            const seasonsRow = document.querySelector(`.seasons-row[data-series-id="${seriesId}"]`);
            const arrow = this.querySelector('.arrow');

            if (seasonsRow.classList.contains('hidden')) {
                seasonsRow.classList.remove('hidden');
                arrow.textContent = '▲';
            } else {
                seasonsRow.classList.add('hidden');
                arrow.textContent = '▼';
                // بستن همه فصل‌ها در این سریال
                const seasonLists = seasonsRow.querySelectorAll('.episode-list');
                seasonLists.forEach(list => list.classList.add('hidden'));
                const seasonArrows = seasonsRow.querySelectorAll('.toggle-season .arrow');
                seasonArrows.forEach(arrow => arrow.textContent = '▼');
            }
        });
    });

    // مدیریت کلیک روی فصل‌ها
    const seasonButtons = document.querySelectorAll('.toggle-season');
    seasonButtons.forEach(button => {
        button.addEventListener('click', function () {
            const seasonId = this.getAttribute('data-season-id');
            const episodeList = document.querySelector(`.episode-list[data-season-id="${seasonId}"]`);
            const arrow = this.querySelector('.arrow');

            if (episodeList.classList.contains('hidden')) {
                episodeList.classList.remove('hidden');
                arrow.textContent = '▲';
            } else {
                episodeList.classList.add('hidden');
                arrow.textContent = '▼';
            }
        });
    });
});