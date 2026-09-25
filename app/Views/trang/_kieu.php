<style>
    * { box-sizing: border-box; }
    body {
        margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
        font-family: "Segoe UI", Roboto, Arial, sans-serif; background: #eef2f7; color: #1f2937; padding: 16px;
    }
    .khung {
        width: 100%; max-width: 400px; background: #fff; border-radius: 12px; padding: 32px 28px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
    }
    h1 { font-size: 22px; margin: 0 0 6px; color: #1e3a8a; line-height: 1.35; }
    .phu { margin: 0 0 22px; color: #4b5563; font-size: 14px; }
    label { display: block; font-weight: 600; font-size: 14px; margin: 14px 0 6px; }
    input {
        width: 100%; padding: 10px 12px; font-size: 15px; border: 1px solid #9ca3af; border-radius: 8px;
    }
    input:focus, button:focus { outline: 3px solid #93c5fd; outline-offset: 1px; }
    button {
        width: 100%; margin-top: 18px; padding: 11px; font-size: 15px; font-weight: 600; color: #fff;
        background: #1d4ed8; border: 0; border-radius: 8px; cursor: pointer;
    }
    button:hover { background: #1e40af; }
    button:disabled { background: #6b7280; cursor: wait; }
    .loi { min-height: 20px; margin: 12px 0 0; color: #b91c1c; font-size: 14px; }
    .thong-tin { margin: 20px 0 4px; display: grid; grid-template-columns: 120px 1fr; row-gap: 10px; font-size: 15px; }
    .thong-tin dt { color: #4b5563; }
    .thong-tin dd { margin: 0; }
</style>
