# Magnet Surface Field Calculator (WordPress Plugin)

A complete WordPress plugin that provides a frontend calculator for estimating magnet surface field strength using a practical axial dipole approximation.

## Features

- Shortcode-based calculator: `[msfc_calculator]`
- Real-time magnetic field estimate in:
  - Tesla (T)
  - Gauss (G)
  - Microtesla (µT)
- Admin settings page under **Settings → Magnet Field Calculator**
- Configurable default input values (Br, thickness, diameter, distance)
- Safe defaults and settings sanitization
- Lightweight vanilla JS and CSS assets
- Uninstall cleanup (`uninstall.php`) removing plugin option data

## Installation

1. Copy this repository into your WordPress `wp-content/plugins/` directory.
2. Ensure the main plugin file is `magnet-surface-field-calculator.php`.
3. Activate **Magnet Surface Field Calculator** from the WordPress admin plugins page.

## Usage

1. Add shortcode to a page/post:

   ```text
   [msfc_calculator]
   ```

2. Optionally set defaults in:
   - **WordPress Admin → Settings → Magnet Field Calculator**

## Calculation Model

The plugin uses a simplified axis field approximation:

- axial and radial attenuation are estimated based on magnet geometry and measurement distance
- intended for quick engineering estimates
- not a substitute for finite element analysis (FEA) or calibrated measurements

## License

GPL-2.0-or-later
