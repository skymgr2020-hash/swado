# Troubleshooting

- `nvidia-smi` fails: do not reinstall drivers blindly. Confirm the provider image includes working NVIDIA drivers.
- Gateway starts but `/ready` is degraded: check `docker compose logs redis gateway`.
- vLLM OOM: reduce `--gpu-memory-utilization`, use an AWQ/GPTQ Qwen3 8B model, or lower max context.
- Whisper OOM: switch `WHISPER_MODEL` from `large-v3-turbo` to `medium` or `small`.
- WebSocket disconnects behind Nginx: verify Upgrade/Connection headers and long proxy timeouts.
- Laravel tool call returns 401: confirm the customer Sanctum token is passed from Flutter and `AI_SERVICE_SECRET` matches both services.
- Prices look wrong: inspect Laravel `/api/ai/tools/calculate_order_totals`; do not patch prices in Python.
