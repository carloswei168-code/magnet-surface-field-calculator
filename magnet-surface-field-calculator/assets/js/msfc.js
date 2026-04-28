(function () {
  'use strict';

  const config = window.MSFC_CONFIG || {};

  const toNumber = (value, fallback = 0) => {
    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : fallback;
  };

  const calculateFieldTesla = ({ br, thicknessMm, diameterMm, distanceMm }) => {
    const radiusM = (diameterMm / 2) / 1000;
    const lengthM = thicknessMm / 1000;
    const gapM = distanceMm / 1000;

    if (radiusM <= 0 || lengthM <= 0 || br <= 0) {
      return 0;
    }

    // Simplified dipole approximation on axis:
    // B(z) ≈ Br * (L / (L + 2z)) * (R^2 / (R^2 + z^2))
    const axialFactor = lengthM / (lengthM + (2 * gapM));
    const radialFactor = (radiusM * radiusM) / ((radiusM * radiusM) + (gapM * gapM));

    return Math.max(0, br * axialFactor * radialFactor);
  };

  const formatNumber = (num, digits = 5) => {
    if (!Number.isFinite(num)) {
      return '-';
    }
    return num.toLocaleString(undefined, {
      maximumFractionDigits: digits,
      minimumFractionDigits: 0,
    });
  };

  const initCalculator = (root) => {
    const inputBr = root.querySelector('[data-msfc-input="br"]');
    const inputThickness = root.querySelector('[data-msfc-input="thickness"]');
    const inputDiameter = root.querySelector('[data-msfc-input="diameter"]');
    const inputDistance = root.querySelector('[data-msfc-input="distance"]');

    const resultTesla = root.querySelector('[data-msfc-result-tesla]');
    const resultGauss = root.querySelector('[data-msfc-result-gauss]');
    const resultMicrotesla = root.querySelector('[data-msfc-result-microtesla]');

    const calculateButton = root.querySelector('[data-msfc-action="calculate"]');

    inputBr.value = toNumber(config.defaultBr, 1.25);
    inputThickness.value = toNumber(config.defaultThickness, 5);
    inputDiameter.value = toNumber(config.defaultDiameter, 10);
    inputDistance.value = toNumber(config.defaultDistance, 0);

    const renderResults = () => {
      const br = Math.max(0, toNumber(inputBr.value));
      const thicknessMm = Math.max(0, toNumber(inputThickness.value));
      const diameterMm = Math.max(0, toNumber(inputDiameter.value));
      const distanceMm = Math.max(0, toNumber(inputDistance.value));

      const tesla = calculateFieldTesla({ br, thicknessMm, diameterMm, distanceMm });
      const gauss = tesla * toNumber(config.gaussConversion, 10000);
      const microtesla = tesla * toNumber(config.microTeslaFactor, 1000000);

      resultTesla.textContent = formatNumber(tesla, 6);
      resultGauss.textContent = formatNumber(gauss, 2);
      resultMicrotesla.textContent = formatNumber(microtesla, 2);
    };

    calculateButton.addEventListener('click', renderResults);
    [inputBr, inputThickness, inputDiameter, inputDistance].forEach((input) => {
      input.addEventListener('input', renderResults);
    });

    renderResults();
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-msfc-root]').forEach(initCalculator);
  });
})();
