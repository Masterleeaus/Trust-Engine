# Trust Signal Evaluation

- Evaluated: 2026-10-04T04:07:38Z
- Evaluator commit: 847efa9dfec546c019396b672e4d99203ae10fa6 (PR head b08409203dfc9f1e6213837ee47bddf02f4bffb4; base 0497585a786e44125eb3ed9ac554321ff7b1a2f3)
- Scenarios: 32 fixed cases; seed 20261004; random sampling: no
- Scenario SHA-256: b0e923ad40cfc76c3537f0123abe4aa230a53c8ed7332353f554459f2837fdd1
- PHP: 8.2.34
- Allow-all baseline exact-output mismatches: 25 / 32

| Metric | Result |
| --- | ---: |
| Trust-level mismatches | 0 / 32 |
| Quality-flag mismatches | 0 / 32 |
| High signals with no GPS | 0 / 6 |
| High signals with accuracy over 500 m | 0 / 5 |
| Correct medium signals for 100 m < accuracy <= 500 m | 8 / 8 |
| Missing-accuracy cases with GPS still marked high | 3 / 3 |
| Missing-accuracy cases explicitly flagged | 3 / 3 |
| Scenario failures | 0 / 32 |

This is a deterministic behavior-conformance evaluation of the GPS signal heuristic. GPS does not prove attendance or truth. The baseline is an illustrative always-high/no-flags bypass, not a competing product.

## Diagnostic finding

The implementation flags missing accuracy as no_accuracy but still returns high when coordinates are present. This behavior is included in the recorded contract results and is visible as a separate caution; high remains a coarse signal only.

## Scenario outcomes

| Scenario | Category | Expected | Actual | Flags | Result |
| --- | --- | --- | --- | --- | --- |
| accurate_device_zero_error | high-accuracy | high | high | expected: ; actual:  | PASS |
| accurate_device_50m | high-accuracy | high | high | expected: ; actual:  | PASS |
| accuracy_boundary_100m | threshold-boundary | high | high | expected: ; actual:  | PASS |
| accurate_missing_source | source-provenance | high | high | expected: ; actual:  | PASS |
| accurate_server_source | source-provenance | high | high | expected: non_device_source; actual: non_device_source | PASS |
| accurate_manual_source | source-provenance | high | high | expected: non_device_source; actual: non_device_source | PASS |
| accurate_device_source | source-provenance | high | high | expected: ; actual:  | PASS |
| accurate_empty_source | source-provenance | high | high | expected: ; actual:  | PASS |
| just_over_100m_medium | threshold-boundary | medium | medium | expected: low_accuracy; actual: low_accuracy | PASS |
| 150m_medium | medium-accuracy | medium | medium | expected: low_accuracy; actual: low_accuracy | PASS |
| 499_99m_medium | threshold-boundary | medium | medium | expected: low_accuracy; actual: low_accuracy | PASS |
| accuracy_boundary_500m_medium | threshold-boundary | medium | medium | expected: low_accuracy; actual: low_accuracy | PASS |
| medium_server_source | source-provenance | medium | medium | expected: low_accuracy, non_device_source; actual: low_accuracy, non_device_source | PASS |
| medium_import_source | source-provenance | medium | medium | expected: low_accuracy, non_device_source; actual: low_accuracy, non_device_source | PASS |
| just_over_500m_low | threshold-boundary | low | low | expected: low_accuracy, very_low_accuracy; actual: low_accuracy, very_low_accuracy | PASS |
| 1000m_low | very-low-accuracy | low | low | expected: low_accuracy, very_low_accuracy; actual: low_accuracy, very_low_accuracy | PASS |
| latitude_missing | missing-position | low | low | expected: no_gps; actual: no_gps | PASS |
| longitude_missing | missing-position | low | low | expected: no_gps; actual: no_gps | PASS |
| both_coordinates_missing | missing-position | low | low | expected: no_gps; actual: no_gps | PASS |
| missing_position_and_600m | missing-position | low | low | expected: no_gps, low_accuracy, very_low_accuracy; actual: no_gps, low_accuracy, very_low_accuracy | PASS |
| 501m_server_source | source-provenance | low | low | expected: low_accuracy, very_low_accuracy, non_device_source; actual: low_accuracy, very_low_accuracy, non_device_source | PASS |
| missing_accuracy_device | missing-accuracy | high | high | expected: no_accuracy; actual: no_accuracy | PASS |
| missing_accuracy_no_source | missing-accuracy | high | high | expected: no_accuracy; actual: no_accuracy | PASS |
| missing_accuracy_server_source | missing-accuracy | high | high | expected: no_accuracy, non_device_source; actual: no_accuracy, non_device_source | PASS |
| equator_accuracy_boundary | coordinate-boundary | high | high | expected: ; actual:  | PASS |
| equator_medium_accuracy | coordinate-boundary | medium | medium | expected: low_accuracy; actual: low_accuracy | PASS |
| another_medium_accuracy | medium-accuracy | medium | medium | expected: low_accuracy, non_device_source; actual: low_accuracy, non_device_source | PASS |
| case-sensitive_device_source | source-provenance | high | high | expected: non_device_source; actual: non_device_source | PASS |
| mobile_source | source-provenance | high | high | expected: non_device_source; actual: non_device_source | PASS |
| missing_accuracy_and_latitude | missing-input | low | low | expected: no_gps, no_accuracy; actual: no_gps, no_accuracy | PASS |
| missing_position_very_low_accuracy | missing-input | low | low | expected: no_gps, low_accuracy, very_low_accuracy; actual: no_gps, low_accuracy, very_low_accuracy | PASS |
| api_source_at_100m | source-provenance | high | high | expected: non_device_source; actual: non_device_source | PASS |
