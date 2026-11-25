// 1. Confirm before dangerous actions: <form data-confirm="Are you sure?">
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    if (!confirm(form.dataset.confirm)) e.preventDefault();
  });
});

// 2. Match form: only show teams registered in the chosen tournament
var tournamentSelect = document.getElementById('tournament_id');
if (tournamentSelect) {
  var teamSelects = document.querySelectorAll('.team-select');
  function filterTeams() {
    teamSelects.forEach(function (sel) {
      Array.from(sel.options).forEach(function (opt) {
        if (!opt.dataset.tournament) return;
        var show = opt.dataset.tournament === tournamentSelect.value;
        opt.hidden = !show;
        opt.disabled = !show;
        if (!show && opt.selected) sel.value = '';
      });
    });
  }
  tournamentSelect.addEventListener('change', filterTeams);
  filterTeams();
}
