// Web Worker: menjalankan SQL user dengan sql.js (SQLite di browser)
importScripts('../../vendor/sqljs/sql-wasm.js');

const ready = initSqlJs({ locateFile: (file) => '../../vendor/sqljs/' + file });

const norm = (v) => (v === null || v === undefined ? null : String(v));

function sameRows(actual, expected, ordered) {
  const a = actual.map((r) => JSON.stringify(r.map(norm)));
  const b = expected.map((r) => JSON.stringify(r.map(norm)));
  if (!ordered) {
    a.sort();
    b.sort();
  }
  return a.length === b.length && a.every((x, i) => x === b[i]);
}

function grade(db, test, sets, error) {
  if (error) return { pass: false, detail: 'Query error: ' + error };

  if (test.type === 'query') {
    // Cek keadaan tabel setelah query user (INSERT/UPDATE/DELETE/CREATE)
    let rows = [];
    try {
      const res = db.exec(test.query);
      rows = res.length ? res[0].values : [];
    } catch (e) {
      return { pass: false, detail: e.message };
    }
    return { pass: sameRows(rows, test.expect, !!test.ordered), detail: '' };
  }

  // type "result": bandingkan tabel hasil query terakhir user
  const last = sets[sets.length - 1];
  if (!last) return { pass: false, detail: 'Query tidak menghasilkan tabel.' };

  if (test.columns) {
    const got = last.columns.map((c) => c.toLowerCase());
    const want = test.columns.map((c) => c.toLowerCase());
    if (JSON.stringify(got) !== JSON.stringify(want)) {
      return { pass: false, detail: 'Kolom yang diharapkan: ' + test.columns.join(', ') };
    }
  }
  if (!test.expect) return { pass: true, detail: '' }; // tes khusus kolom
  return { pass: sameRows(last.values, test.expect, !!test.ordered), detail: '' };
}

self.onmessage = async (e) => {
  const { setup, code, tests } = e.data;
  let SQL;
  try {
    SQL = await ready;
  } catch (err) {
    self.postMessage({ fatal: 'sql.js gagal dimuat: ' + err.message });
    return;
  }

  const db = new SQL.Database();
  try {
    if (setup) db.exec(setup);
  } catch (err) {
    self.postMessage({ fatal: 'Setup tabel gagal: ' + err.message });
    return;
  }

  let sets = [];
  let error = null;
  let changes = 0;
  try {
    sets = db.exec(code);
    changes = db.getRowsModified();
  } catch (err) {
    error = err.message;
  }

  const results = tests ? tests.map((t) => grade(db, t, sets, error)) : null;
  self.postMessage({ sets: sets.map((s) => ({ columns: s.columns, values: s.values })), error, changes, results });
  db.close();
};
