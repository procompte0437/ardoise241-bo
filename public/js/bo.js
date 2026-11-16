(function () {
  var counts = {};

  function $(id) { return document.getElementById(id); }
  function csrf() { return (window.LESSY && LESSY.csrf) || ''; }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function fmt(n) { return Number(n || 0).toLocaleString('fr-FR') + ' F'; }
  function toast(m) {
    var t = $('toast');
    if (!t) return;
    t.textContent = m;
    t.classList.add('on');
    clearTimeout(toast._t);
    toast._t = setTimeout(function () { t.classList.remove('on'); }, 2600);
  }
  function openModal(h) {
    $('modal').innerHTML = '<div class="ov" onclick="if(event.target===this)closeModal()"><div class="sheet">' + h + '</div></div>';
  }
  function closeModal() { $('modal').innerHTML = ''; }
  window.closeModal = closeModal;

  if (window.LESSY && LESSY.error) {
    /* page-level errors are already printed */
  }
  var existing = $('toast');
  if (existing && existing.textContent.trim()) toast(existing.textContent.trim());

  function askCode(after) {
    openModal('<h3>Code admin</h3><p>Entrez le code pour modifier les noms et les images.</p><input id="code" class="field" type="password" inputmode="numeric" maxlength="8" placeholder="Code" autocomplete="off"><div class="err" id="err"></div><div class="btns"><button class="btn out" type="button" onclick="closeModal()">Annuler</button><button class="btn dark" type="button" id="checkCode">Entrer</button></div>');
    var input = $('code');
    input.focus();
    function go() {
      fetch('/admin/entrer', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ code: input.value })
      }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
          if (!res.ok) {
            $('err').textContent = res.d.message || 'Code incorrect.';
            input.value = '';
            return;
          }
          if (after) sessionStorage.setItem('lessy_after', JSON.stringify(after));
          location.reload();
        }).catch(function () { $('err').textContent = 'Serveur injoignable.'; });
    }
    $('checkCode').onclick = go;
    input.onkeydown = function (e) { if (e.key === 'Enter') go(); };
  }

  function needAdmin(after) {
    if (window.LESSY && LESSY.admin) return true;
    askCode(after);
    return false;
  }

  var lock = $('lockbtn');
  if (lock) {
    lock.onclick = function () {
      if (window.LESSY && LESSY.admin) {
        var form = $('leaveForm');
        if (form) form.submit();
      } else askCode(null);
    };
  }

  var list = $('list');
  var q = $('q');
  if (q && list) {
    q.addEventListener('input', function () {
      var v = q.value.trim().toLowerCase();
      var shown = 0;
      var items = list.querySelectorAll('.item');
      items.forEach(function (el) {
        var ok = !v || (el.dataset.name || '').toLowerCase().indexOf(v) > -1;
        el.style.display = ok ? '' : 'none';
        if (ok) shown++;
      });
      var empty = $('emptysearch');
      if (!empty) return;
      if (!v || shown || !items.length) {
        empty.style.display = 'none';
      } else {
        empty.style.display = '';
        empty.textContent = 'Aucun nom ne correspond à « ' + q.value + ' ».';
      }
    });
    list.addEventListener('click', function (e) {
      var pen = e.target.closest('.pen');
      var item = e.target.closest('.item');
      if (!item) return;
      if (pen) {
        e.stopPropagation();
        e.preventDefault();
        openRename(item);
        return;
      }
      openChoose(item);
    });
    list.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      var item = e.target.closest('.item');
      if (item) openChoose(item);
    });
  }

  function openChoose(item) {
    var id = item.dataset.id;
    openModal('<h3>' + esc(item.dataset.name) + '</h3><p>Bilan actuel : <b>' + esc(item.dataset.debt) + '</b></p>'
      + '<a class="btn dark" style="width:100%;margin-bottom:10px;display:block;text-align:center" href="/bilan/' + id + '">Voir le bilan</a>'
      + '<a class="btn out" style="width:100%;display:block;text-align:center" href="/session/' + id + '">Nouvelle session (images)</a>');
  }

  function openRename(item) {
    if (!needAdmin({ type: 'rename', id: item.dataset.id })) return;
    openModal('<h3>Modifier le nom</h3><form method="post" action="/noms/' + item.dataset.id + '/renommer"><input type="hidden" name="_token" value="' + esc(csrf()) + '"><input id="rn" name="name" class="field" maxlength="40" value="' + esc(item.dataset.name) + '"><div class="err" id="err"></div><div class="btns"><button class="btn out" type="button" onclick="closeModal()">Annuler</button><button class="btn dark" type="submit">Enregistrer</button></div></form>');
    var input = $('rn');
    if (input) { input.focus(); input.select(); }
  }

  var addNames = $('addNames');
  if (addNames) {
    addNames.onclick = function () {
      var room = addNames.dataset.room || '0';
      openModal('<h3>Ajouter des noms</h3><p>Un nom par ligne. Vous pouvez en coller plusieurs d\'un coup (' + esc(room) + ' places restantes).</p><form method="post" action="/noms"><input type="hidden" name="_token" value="' + esc(csrf()) + '"><textarea id="nn" name="text" class="field" placeholder="Ex :\nMarie\nJean Paul\nAïcha"></textarea><div class="btns"><button class="btn out" type="button" onclick="closeModal()">Annuler</button><button class="btn dark" type="submit">Ajouter</button></div></form>');
    };
  }

  var changeCode = $('changeCode');
  if (changeCode) {
    changeCode.onclick = function () {
      openModal('<h3>Changer le code</h3><p>Nouveau code (4 chiffres) :</p><form method="post" action="/reglages/code"><input type="hidden" name="_token" value="' + esc(csrf()) + '"><input id="nc" name="code" class="field" type="password" inputmode="numeric" maxlength="4" placeholder="4 chiffres"><div class="btns"><button class="btn out" type="button" onclick="closeModal()">Annuler</button><button class="btn dark" type="submit">Enregistrer</button></div></form>');
    };
  }

  var eraseBtn = $('eraseBtn');
  if (eraseBtn) {
    eraseBtn.onclick = function () {
      openModal('<h3>Effacer le bilan</h3><p>Le bilan de <b>' + esc(eraseBtn.dataset.name) + '</b> (' + esc(eraseBtn.dataset.debt) + ') sera effacé définitivement. Entrez le code pour confirmer, sinon il reste inchangé.</p><form method="post" action="' + esc(eraseBtn.dataset.action) + '"><input type="hidden" name="_token" value="' + esc(csrf()) + '"><input id="ec" name="code" class="field" type="password" inputmode="numeric" maxlength="8" placeholder="Code"><div class="btns"><button class="btn out" type="button" onclick="closeModal()">Annuler</button><button class="btn danger" type="submit">Effacer</button></div></form>');
      var input = $('ec');
      if (input) input.focus();
    };
  }

  var payBtn = $('payBtn');
  if (payBtn) {
    payBtn.onclick = function () {
      openModal('<h3>Enregistrer un paiement</h3><p>Montant payé en FCFA :</p><form method="post" action="' + esc(payBtn.dataset.action) + '"><input type="hidden" name="_token" value="' + esc(csrf()) + '"><input id="amt" name="amount" class="field" type="number" inputmode="numeric" min="1" placeholder="Ex : 5000"><div class="btns"><button class="btn out" type="button" onclick="closeModal()">Annuler</button><button class="btn dark" type="submit">Enregistrer</button></div></form>');
      var input = $('amt');
      if (input) input.focus();
    };
  }

  document.querySelectorAll('[data-delete]').forEach(function (btn) {
    btn.onclick = function (e) {
      e.preventDefault();
      e.stopPropagation();
      openModal('<h3>Confirmer</h3><p>Supprimer « ' + esc(btn.dataset.name) + ' » ?</p><div class="btns"><button class="btn out" type="button" onclick="closeModal()">Annuler</button><button class="btn danger" type="button" id="yesDelete">Supprimer</button></div>');
      $('yesDelete').onclick = function () {
        var form = document.createElement('form');
        form.method = 'post';
        form.action = btn.dataset.delete;
        form.innerHTML = '<input type="hidden" name="_token" value="' + esc(csrf()) + '">';
        document.body.appendChild(form);
        form.submit();
      };
    };
  });

  var grid = $('grid');
  function sessionTotal() {
    var t = 0;
    if (!grid) return 0;
    grid.querySelectorAll('.tile').forEach(function (tile) {
      t += (counts[tile.dataset.id] || 0) * Number(tile.dataset.price || 0);
    });
    return t;
  }
  function refreshTile(id) {
    var tile = $('t_' + id);
    if (!tile) return;
    var n = counts[id] || 0;
    tile.classList.toggle('on', n > 0);
    var badge = tile.querySelector('.badge');
    if (badge) badge.textContent = '×' + n;
    var tot = $('tot');
    if (tot) tot.textContent = fmt(sessionTotal());
  }
  if (grid) {
    grid.addEventListener('click', function (e) {
      var pen = e.target.closest('.pen');
      if (pen) {
        e.preventDefault();
        e.stopPropagation();
        editFood(pen.closest('.tile'));
        return;
      }
      var tile = e.target.closest('.tile');
      if (!tile) return;
      var id = tile.dataset.id;
      if (e.target.closest('.minus')) {
        if (counts[id] > 0) {
          counts[id]--;
          if (!counts[id]) delete counts[id];
        }
        refreshTile(id);
        return;
      }
      counts[id] = (counts[id] || 0) + 1;
      refreshTile(id);
    });
  }
  var clearBtn = $('clearSession');
  if (clearBtn) {
    clearBtn.onclick = function () {
      if (!sessionTotal()) return;
      counts = {};
      if (!grid) return;
      grid.querySelectorAll('.tile').forEach(function (tile) { refreshTile(tile.dataset.id); });
    };
  }
  function editFood(tile) {
    if (!tile) return;
    if (!needAdmin({ type: 'food', id: tile.dataset.id })) return;
    var person = grid ? grid.dataset.person : '';
    openModal('<h3>Modifier l\'image</h3><form method="post" action="/aliments/' + tile.dataset.id + '" enctype="multipart/form-data"><input type="hidden" name="_token" value="' + esc(csrf()) + '"><input type="hidden" name="back" value="session"><input type="hidden" name="person" value="' + esc(person) + '"><label class="btn dark" style="display:block;text-align:center;cursor:pointer;margin-bottom:12px">Changer la photo<input type="file" name="photo" accept="image/*" hidden onchange="this.form.submit()"></label><p>Nom :</p><input name="name" class="field" maxlength="30" value="' + esc(tile.dataset.name) + '" style="margin-bottom:12px"><p>Montant de cette image (en F) :</p><input name="price" class="field" type="number" inputmode="numeric" min="0" value="' + esc(tile.dataset.price) + '"><div class="btns"><button class="btn out" type="button" onclick="closeModal()">Annuler</button><button class="btn dark" type="submit">Enregistrer</button></div></form>');
  }
  var validate = $('validate');
  if (validate && grid) {
    validate.onclick = function () {
      var total = sessionTotal();
      if (total <= 0) { toast('Touchez des images pour ajouter des montants.'); return; }
      fetch(grid.dataset.validate, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ counts: counts })
      }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
          if (!res.ok) { toast(res.d.message || 'Erreur'); return; }
          toast(fmt(res.d.amount) + ' ajoutés au bilan.');
          setTimeout(function () { location.href = res.d.redirect; }, 350);
        }).catch(function () { toast('Serveur injoignable.'); });
    };
  }
  var leave = $('leave');
  if (leave && grid) {
    leave.addEventListener('click', function (e) {
      if (sessionTotal() <= 0) return;
      e.preventDefault();
      openModal('<h3>Confirmer</h3><p>La session en cours n\'a pas été validée. Quitter sans l\'enregistrer ?</p><div class="btns"><button class="btn out" type="button" onclick="closeModal()">Annuler</button><button class="btn danger" type="button" id="yesLeave">Quitter</button></div>');
      $('yesLeave').onclick = function () { location.href = leave.dataset.home || '/noms'; };
    });
  }

  try {
    var after = JSON.parse(sessionStorage.getItem('lessy_after') || 'null');
    if (after && window.LESSY && LESSY.admin) {
      sessionStorage.removeItem('lessy_after');
      if (after.type === 'rename') {
        var item = list && list.querySelector('.item[data-id="' + after.id + '"]');
        if (item) openRename(item);
      }
      if (after.type === 'food' && grid) {
        var tile = $('t_' + after.id);
        if (tile) editFood(tile);
      }
    } else if (after && !(window.LESSY && LESSY.admin)) {
      sessionStorage.removeItem('lessy_after');
    }
  } catch (e) {}
})();
