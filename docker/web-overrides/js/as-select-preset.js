/**
 * as-select-preset.js — fleet-SELECTION presets for the offence fleet-pick page.
 *
 * The step before the deploy board: the page where you tick which stand-by
 * fleets to send and pick the capital. This adds a "select preset" dropdown that
 * autofills those checkboxes (and the capital radio) from one of the player's
 * saved attack templates — the SAME templates the deploy board saves/loads, so a
 * named template drives the whole attack end to end (which fleets, then where).
 *
 * It reuses the deploy board's template blob (emitted by the engine into
 * #as-attack-templates) with no new storage:
 *     T<id>|<name>
 *     F<fleet_id>|<cmd>|<x>|<y>      (the capital row carries x=y=0)
 * so the member fleet ids are every F row and the capital is the (0,0) row.
 *
 * A fleet in a template that is no longer on stand-by simply can't be ticked
 * (its row isn't on the page); the status line reports how many matched. Shared
 * across all six offence fleet-pick pages (siege, blockade, the four empire
 * invades) — they all render the same FLEET{n}/FLEET{n}_ID/CAPITAL markup.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var blobEl = document.getElementById('as-attack-templates');
    if (!blobEl) return;

    /* 1. The fleet checkboxes on the page (names FLEET0, FLEET3, ... — sparse,
       since the engine indexes by absolute fleet-list position). */
    var checkboxes = [];
    var allCb = document.querySelectorAll('input[type="checkbox"]');
    for (var i = 0; i < allCb.length; i++)
      if (/^FLEET\d+$/.test(allCb[i].name)) checkboxes.push(allCb[i]);
    if (!checkboxes.length) return;
    var form = checkboxes[0].form;
    if (!form) return;

    // The fleet id behind a checkbox: prefer its sibling FLEET{n}_ID hidden
    // input; fall back to the CAPITAL radio in the same table row (every offence
    // page renders one per fleet, valued with the fleet id).
    function fleetIdOf(cb) {
      var hid = form.querySelector('input[name="' + cb.name + '_ID"]');
      if (hid && hid.value) return String(parseInt(hid.value, 10));
      var row = cb.closest ? cb.closest('tr') : null;
      if (row) {
        var r = row.querySelector('input[name="CAPITAL"]');
        if (r && r.value) return String(parseInt(r.value, 10));
      }
      return null;
    }

    /* 2. Parse the template blob (same format the deploy board picker reads). */
    var templates = [], cur = null;
    var lines = (blobEl.textContent || '').split('\n');
    for (var li = 0; li < lines.length; li++) {
      var ln = lines[li]; if (!ln) continue;
      if (ln.charAt(0) === 'T') {
        var rest = ln.substring(1), barAt = rest.indexOf('|');
        cur = { id: rest.substring(0, barAt), name: rest.substring(barAt + 1),
                members: {}, count: 0, capital: null };
        templates.push(cur);
      } else if (ln.charAt(0) === 'F' && cur) {
        var p = ln.substring(1).split('|');
        var fid = String(parseInt(p[0], 10));
        if (!cur.members[fid]) { cur.members[fid] = true; cur.count++; }
        if (parseInt(p[2], 10) === 0 && parseInt(p[3], 10) === 0) cur.capital = fid;
      }
    }
    if (!templates.length) return;          // nothing to offer — stay invisible

    var status = document.createElement('span');
    status.style.cssText = 'margin-left:8px;color:#8fb0ff;';

    /* 3. Apply a template: tick the matching fleets (untick the rest) and set the
       capital radio. Fleets in the template but not on stand-by are skipped. */
    function applyTemplate(tpl) {
      if (!tpl) return;
      var matched = 0;
      for (var i = 0; i < checkboxes.length; i++) {
        var fid = fleetIdOf(checkboxes[i]);
        var on = !!(fid && tpl.members[fid]);
        checkboxes[i].checked = on;
        if (on) matched++;
      }
      if (tpl.capital) {
        var r = form.querySelector('input[name="CAPITAL"][value="' + tpl.capital + '"]');
        if (r) r.checked = true;
      }
      var missing = tpl.count - matched;
      status.textContent = 'Selected ' + matched + ' of ' + tpl.count + ' fleet(s)' +
        (missing > 0 ? ' — ' + missing + ' not on stand-by' : '');
    }

    /* 4. Picker bar, inserted just above the fleet table. */
    var table = checkboxes[0];
    while (table && table.tagName !== 'TABLE') table = table.parentNode;
    if (!table) return;

    var bar = document.createElement('div');
    bar.style.cssText = 'margin:0 0 6px;text-align:center;font:12px sans-serif;color:#cdd9f2;';

    var sel = document.createElement('select');
    sel.style.cssText = 'background:#0d1a30;color:#cdd9f2;border:1px solid #2f4a78;' +
                        'border-radius:4px;padding:2px;margin-right:4px;';
    var opt0 = document.createElement('option');
    opt0.value = ''; opt0.textContent = '— select preset —';
    sel.appendChild(opt0);
    for (var t = 0; t < templates.length; t++) {
      var o = document.createElement('option');
      o.value = templates[t].id; o.textContent = templates[t].name;
      sel.appendChild(o);
    }
    sel.onchange = function () {
      for (var t2 = 0; t2 < templates.length; t2++)
        if (templates[t2].id === sel.value) { applyTemplate(templates[t2]); return; }
      status.textContent = '';
    };
    bar.appendChild(sel);

    function mkBtn(label) {
      var b = document.createElement('button');
      b.type = 'button'; b.textContent = label;
      b.style.cssText = 'background:#16243a;color:#cde;border:1px solid #2a4a6a;' +
                        'border-radius:4px;padding:2px 8px;margin:0 2px;cursor:pointer;';
      bar.appendChild(b); return b;
    }
    mkBtn('Apply').onclick = function () { if (sel.value) sel.onchange(); };
    mkBtn('Clear').onclick = function () {
      for (var i = 0; i < checkboxes.length; i++) checkboxes[i].checked = false;
      var caps = form.querySelectorAll('input[name="CAPITAL"]');
      for (var c = 0; c < caps.length; c++) caps[c].checked = false;
      sel.value = ''; status.textContent = '';
    };

    bar.appendChild(status);

    table.parentNode.insertBefore(bar, table);
  });
}());
