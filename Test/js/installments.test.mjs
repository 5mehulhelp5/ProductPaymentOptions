// Tests of the installment calculation (view/frontend/web/js/installments.js), run with `node --test Test/js`.
// Every expected value below was calculated by hand (exact decimals, half-up rounding to cents), not taken from the code.
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import vm from 'node:vm';

// The module is AMD (RequireJS): load it with a minimal `define`.
let installments;
vm.runInNewContext(readFileSync(new URL('../../view/frontend/web/js/installments.js', import.meta.url), 'utf8'), {
  define: (deps, factory) => {
    installments = factory();
  },
  Math,
  Number,
  parseFloat,
  parseInt,
  isNaN,
});

// Values come from another vm context: compare plain data, not prototypes.
const plain = (value) => JSON.parse(JSON.stringify(value));
const cents = (value) => installments.roundMoney(value);
const rows = (plan) => plain(plan.installments).map(({ count, amount, withInterest }) => [count, cents(amount), withInterest]);

const CHECKMO = { discount_fixed: '0', discount_percentage: '10', max_installments: '6', no_interest_installments: '6', monthly_interest_rate: '0' };
const BANK = { discount_fixed: '50', discount_percentage: '0', max_installments: '12', no_interest_installments: '3', monthly_interest_rate: '1.99' };
const MIN = 150;

test('Tabela Price: R$ 1.184,56 at 1,99 % a.m. in 4x is R$ 311,02 and in 9x is R$ 145,06', () => {
  assert.equal(cents(installments.priceTablePayment(1184.56, 4, 1.99)), 311.02);
  assert.equal(cents(installments.priceTablePayment(1184.56, 9, 1.99)), 145.06);
});

test('Tabela Price is not the old principal x (1 + i)^n / n formula', () => {
  assert.notEqual(cents(installments.priceTablePayment(1184.56, 4, 1.99)), 320.43);
});

test('without interest the installment is principal / n', () => {
  assert.equal(installments.priceTablePayment(1000, 4, 0), 250);
  assert.equal(cents(installments.priceTablePayment(1000, 3, 0)), 333.33);
});

test('Bank Transfer on R$ 1.234,56: 1x-3x interest-free, 4x-8x with interest, 9x-12x hidden below R$ 150', () => {
  const plan = installments.calculate(1234.56, BANK, MIN);

  assert.equal(cents(plan.final), 1184.56);
  assert.equal(cents(plan.discount), 50);
  assert.deepEqual(rows(plan), [
    [1, 1184.56, false], [2, 592.28, false], [3, 394.85, false],
    [4, 311.02, true], [5, 251.24, true], [6, 211.4, true], [7, 182.96, true], [8, 161.63, true],
  ]);
});

test('Check / Money order on R$ 1.234,56: 10 % off and 6 interest-free installments', () => {
  const plan = installments.calculate(1234.56, CHECKMO, MIN);

  assert.equal(cents(plan.final), 1111.1);
  assert.equal(cents(plan.discount), 123.46);
  assert.deepEqual(rows(plan), [
    [1, 1111.1, false], [2, 555.55, false], [3, 370.37, false], [4, 277.78, false], [5, 222.22, false], [6, 185.18, false],
  ]);
});

test('only the displayed values are rounded: the discounted price keeps its fraction', () => {
  const plan = installments.calculate(1234.56, CHECKMO, MIN);

  assert.ok(Math.abs(plan.final - 1111.104) < 1e-9);
  assert.ok(Math.abs(plan.installments[5].amount - 185.184) < 1e-9);
});

test('configurable prices of the store (R$ 1.100,00, 1.500,00 and 2.000,99) with the Bank Transfer row', () => {
  assert.deepEqual(rows(installments.calculate(1100, BANK, MIN)), [
    [1, 1050, false], [2, 525, false], [3, 350, false], [4, 275.69, true], [5, 222.7, true], [6, 187.39, true], [7, 162.18, true],
  ]);
  assert.deepEqual(rows(installments.calculate(1500, BANK, MIN)).map(([count, amount]) => [count, amount]), [
    [1, 1450], [2, 725], [3, 483.33], [4, 380.71], [5, 307.54], [6, 258.78], [7, 223.96], [8, 197.85], [9, 177.56], [10, 161.34],
  ]);
  assert.deepEqual(rows(installments.calculate(2000.99, BANK, MIN)).slice(-3), [
    [10, 217.08, true], [11, 199.23, true], [12, 184.37, true],
  ]);
});

test('a price whose installments are all under the minimum lists none, but still has a final price', () => {
  const plan = installments.calculate(61, BANK, MIN);

  assert.equal(cents(plan.final), 11);
  assert.deepEqual(plain(plan.installments), []);
});

test('the minimum installment value is inclusive', () => {
  const plan = installments.calculate(300, { max_installments: '2', no_interest_installments: '2' }, 150);

  assert.deepEqual(rows(plan), [[1, 300, false], [2, 150, false]]);
});

test('a fixed discount wins over a percentage one, and the price never goes below zero', () => {
  const both = installments.calculate(1000, { discount_fixed: '100', discount_percentage: '50' }, 0);
  const huge = installments.calculate(30, { discount_fixed: '100' }, 0);

  assert.equal(both.final, 900);
  assert.equal(huge.final, 0);
  assert.equal(huge.discount, 30);
});

test('no discount configured: no discount line', () => {
  const plan = installments.calculate(500, { discount_fixed: '', discount_percentage: '' }, 0);

  assert.equal(plan.hasDiscount, false);
  assert.equal(plan.final, 500);
});

test('empty or invalid configuration counts as zero instead of producing NaN', () => {
  const plan = installments.calculate(1000, { max_installments: '3', no_interest_installments: '', monthly_interest_rate: 'abc' }, 0);

  assert.deepEqual(rows(plan), [[1, 1000, true], [2, 500, true], [3, 333.33, true]]);
  assert.ok(plan.installments.every((installment) => Number.isFinite(installment.amount)));
});

test('one installment (or none) lists no installment plan', () => {
  assert.deepEqual(plain(installments.calculate(1000, { max_installments: '1' }, 0).installments), []);
  assert.deepEqual(plain(installments.calculate(1000, {}, 0).installments), []);
});
