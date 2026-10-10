<style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Segoe UI", Roboto, Arial, sans-serif; background: #eef2f7; color: #1f2937; }
    .dau-trang {
        display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
        padding: 18px 24px; background: #fff; box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
    }
    h1 { font-size: 22px; margin: 2px 0; color: #1e3a8a; }
    h2 { font-size: 18px; margin: 0 0 12px; color: #1e3a8a; }
    h3 { font-size: 16px; margin: 0 0 6px; }
    .phu { margin: 0; color: #4b5563; font-size: 14px; }
    .noi-dung { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; }
    .the { background: #fff; border-radius: 12px; padding: 20px; margin: 0 0 18px; box-shadow: 0 4px 16px rgba(15, 23, 42, .06); }
    .muc { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; margin: 10px 0; }
    .muc-con { border-left: 3px solid #bfdbfe; padding: 6px 0 6px 12px; margin: 10px 0 0; }
    .hang { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 8px 0 0; }
    .luoi-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; align-items: end; margin: 8px 0; }
    label { display: block; font-weight: 600; font-size: 14px; }
    input, select, textarea {
        display: block; width: 100%; margin-top: 4px; padding: 8px 10px; font: inherit; font-size: 14px; font-weight: 400;
        border: 1px solid #9ca3af; border-radius: 8px; background: #fff;
    }
    textarea { min-height: 60px; }
    button {
        padding: 8px 14px; font-size: 14px; font-weight: 600; color: #fff; background: #1d4ed8;
        border: 0; border-radius: 8px; cursor: pointer;
    }
    button:hover { background: #1e40af; }
    button:disabled { background: #6b7280; cursor: wait; }
    .nut-phu { background: #fff; color: #1d4ed8; border: 1px solid #1d4ed8; }
    .nut-phu:hover { background: #eff6ff; }
    .nut-nguy { background: #b91c1c; }
    .nut-nguy:hover { background: #991b1b; }
    input:focus, select:focus, textarea:focus, button:focus { outline: 3px solid #93c5fd; outline-offset: 1px; }
    .nhan { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #e5e7eb; color: #374151; }
    .nhan-pending { background: #fef3c7; color: #92400e; }
    .nhan-approved, .nhan-completed { background: #dcfce7; color: #166534; }
    .nhan-rejected, .nhan-revision_requested, .nhan-overdue { background: #fee2e2; color: #991b1b; }
    .nhan-in_progress { background: #dbeafe; color: #1e40af; }
    .nhan-accepted { background: #166534; color: #fff; }
    .loi { min-height: 18px; margin: 6px 0; color: #b91c1c; font-size: 14px; }
    .ket-qua { min-height: 18px; margin: 6px 0; color: #166534; font-size: 14px; }
    .chua-doc { font-weight: 600; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    th { background: #f8fafc; }
    .cuon-ngang { overflow-x: auto; }
    .khoi-bao-cao { border-top: 1px solid #e5e7eb; padding: 14px 0 6px; margin-top: 10px; }
    .bieu-do { display: block; width: 100%; height: auto; margin: 6px 0; }
    .bieu-do .luoi { stroke: #e5e7eb; stroke-width: 1; }
    .bieu-do .truc { font-size: 11px; fill: #6b7280; }
    .bieu-do .nhan-dong { font-size: 12px; fill: #374151; }
    .bieu-do .nhan-tong { font-size: 12px; font-weight: 600; fill: #1f2937; }
    .bieu-do .doan:hover { opacity: .8; }
    .chu-giai { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 13px; color: #374151; margin: 6px 0; }
    .o-mau { display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 6px; vertical-align: -1px; }
    th.so, td.so { text-align: right; font-variant-numeric: tabular-nums; }
    details summary { cursor: pointer; font-size: 14px; color: #1d4ed8; margin: 6px 0; }
    .nut-tai { margin-left: auto; padding: 6px 12px; font-size: 14px; font-weight: 600; color: #1d4ed8; border: 1px solid #1d4ed8; border-radius: 8px; text-decoration: none; }
    .nut-tai:hover { background: #eff6ff; }
    .muc-con .nut-tai, li .nut-tai { margin-left: 0; padding: 2px 10px; font-size: 13px; }
</style>
