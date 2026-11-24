const { readFileSync } = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = readFileSync(require('node:path').join(__dirname, '../checkout.js'), 'utf8');

function setup() {
  let prefilter, tick, completed, notice, calls = 0;
  const button = { disabled: false };
  const form = {
    parentNode: { insertBefore(n) { notice = n; } },
    querySelector() { return button; }
  };
  const document = {
    querySelector() { return form; },
    getElementById() { return notice; },
    createElement() { return { textContent: '', hidden: false, setAttribute() {} }; }
  };
  const window = {
    location: { href: 'https://store.example/finalizar-compra/' },
    setTimeout(fn, ms) { assert.equal(ms, 12000); tick = fn; return 1; },
    clearTimeout() { tick = null; }
  };
  vm.runInNewContext(source, {
    URL, document, window,
    wc_checkout_params: { checkout_url: '/?wc-ajax=checkout' },
    jQuery: { ajaxPrefilter(fn) { prefilter = fn; } }
  });
  const options = { url: '/?wc-ajax=checkout', type: 'POST', error() { calls++; } };
  const xhr = { statusText: 'success', always(fn) { completed = fn; } };
  return {
    options, xhr, button,
    start(overrides = {}) { Object.assign(options, overrides); prefilter(options, {}, xhr); },
    slow() { tick(); },
    complete() { completed(); },
    get notice() { return notice; },
    get timer() { return tick; },
    get calls() { return calls; }
  };
}

const normal = setup();
normal.start();
assert.equal(normal.options.timeout, 60000);
assert.equal(normal.notice.hidden, true);
normal.slow();
assert.match(normal.notice.textContent, /no vuelvas a enviarlo/);
normal.complete();
assert.equal(normal.notice.hidden, true);
assert.equal(normal.button.disabled, false);
assert.equal(normal.timer, null);

const timeout = setup();
timeout.start();
timeout.xhr.statusText = 'timeout';
timeout.slow();
timeout.complete(); // jQuery runs prefilter-registered callbacks first.
timeout.options.error(timeout.xhr, 'timeout', 'timeout');
assert.equal(timeout.calls, 1);
assert.equal(timeout.button.disabled, true);
assert.equal(timeout.notice.hidden, false);
assert.match(timeout.notice.textContent, /podría haberse registrado/);

const fastError = setup();
fastError.start();
fastError.complete();
fastError.options.error(fastError.xhr, 'parsererror', 'Invalid JSON');
assert.equal(fastError.calls, 1);
assert.equal(fastError.button.disabled, true);
assert.equal(fastError.timer, null);

const validation = setup();
const success = () => 'WooCommerce handles success and validation';
validation.start({ success });
validation.complete();
assert.equal(validation.options.success, success);
assert.equal(validation.button.disabled, false);

for (const changes of [
  { url: '/?wc-ajax=update_order_review' },
  { url: 'https://other.example/?wc-ajax=checkout' },
  { url: '/different-path?wc-ajax=checkout' },
  { type: 'GET' }
]) {
  const unrelated = setup(); unrelated.start(changes);
  assert.equal(unrelated.options.timeout, undefined);
  assert.equal(unrelated.notice, undefined);
}
for (const [value, expected] of [[5000, 5000], [120000, 60000], [0, 60000]]) {
  const custom = setup(); custom.start({ timeout: value });
  assert.equal(custom.options.timeout, expected);
}
console.log('PASS: success/validation preserved, slow response, timeout, malformed response, request isolation and timeout bounds.');
