<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #007bff;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
            color: #333333;
            line-height: 1.6;
        }
        .content h2 {
            font-size: 20px;
            color: #007bff;
        }
        .footer {
            background-color: #f8f8f8;
            text-align: center;
            padding: 15px;
            font-size: 14px;
            color: #666666;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>KYC Verification Complete</h1>
        </div>
        <div class="content">
            <h2>Congratulations, {{$name}}!</h2>
            <p>
                We are thrilled to inform you that your KYC (Know Your Customer) verification process has been successfully completed.
            </p>
            <p>
                You now have full access to all our features and services. We appreciate your cooperation in helping us maintain a secure and trusted environment for all users.
            </p>
            {{-- <a href="[Link to Dashboard]" class="btn">Go to Dashboard</a> --}}
        </div>
        <div class="footer">
            <p>Need help? Contact our <a href="mailto:support@mycomeso.com">Support Team</a></p>
            <p>Thank you for choosing MyComeso</p>
        </div>
    </div>
</body>
</html>
