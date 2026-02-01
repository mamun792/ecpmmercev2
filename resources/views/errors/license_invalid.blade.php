<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Invalid</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            margin-top: 0;
            font-size: 2.2rem;
            margin-bottom: 0.5em;
            color: #e74c3c;
        }

        p {
            font-size: 1.1rem;
            line-height: 1.5;
            margin-bottom: 1.5em;
        }

        .status-icon {
            font-size: 4rem;
            margin-bottom: 0.5em;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5em 1.5em;
            border-radius: 50px;
            font-weight: bold;
            margin-bottom: 1.5em;
            background-color: #fadbd8;
            color: #e74c3c;
        }

        .contact-info {
            margin-top: 2em;
            padding-top: 1.5em;
            border-top: 1px solid #eee;
        }

        .contact-info p {
            font-size: 0.95rem;
            color: #7f8c8d;
        }

        .contact-email {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }

        .contact-email:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                width: 85%;
                padding: 20px;
            }

            h1 {
                font-size: 1.8rem;
            }

            p {
                font-size: 1rem;
            }

            .status-icon {
                font-size: 3rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                width: 90%;
                padding: 15px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .status-badge {
                padding: 0.4em 1.2em;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="status-icon">🚫</div>
        <h1>License Invalid</h1>
        <div class="status-badge">Inactive</div>
        <p>Your application license is not valid. Please contact support or purchase a new license to continue using
            this application.</p>

        <div class="contact-info">
            <p>If you believe this is an error, please contact our support team at <a href="mailto:support@example.com"
                    class="contact-email">support@example.com</a></p>
            <p>License ID: <strong>

                    {{ config('license.key') ?? 'N/A' }}
                </strong></p>
        </div>
    </div>
</body>

</html>
