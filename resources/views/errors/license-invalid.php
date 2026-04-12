<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Error | Unauthorized</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .error-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-width: 500px;
            text-align: center;
            border-top: 5px solid #ef4444; /* red */
        }
        h1 { color: #1e293b; font-size: 24px; margin-bottom: 10px; }
        .error-code {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        p { color: #64748b; line-height: 1.6; margin-bottom: 30px; }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">Error #SYS-403</div>
        <h1>License Verification Failed</h1>
        <p>
            The application cannot run because the license key is invalid, expired, or locked to a different domain.
            <br><br>
            <strong>Reason:</strong> <?= isset($errorMsg) ? htmlspecialchars($errorMsg) : 'Unknown Error' ?>
        </p>
        <div class="footer">
            Please contact the system administrator or the software provider to resolve this issue.
        </div>
    </div>
</body>
</html>
