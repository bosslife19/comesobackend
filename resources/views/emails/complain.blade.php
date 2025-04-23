<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
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
            background-color: #28a745;
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
            color: #28a745;
        }
        .transaction-box {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .transaction-box p {
            margin: 0;
            font-size: 16px;
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
            background-color: #28a745;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Complainant Username: {{$name}}</h1>
        </div>
        <div class="content">
            <h2>Complainant Email {{$email}}</h2>
            <p>
                {{$complain}}
            </p>
           
            {{-- <p>
                User's email: {{$email}}
            </p> --}}
            {{-- <a href="[Link to Transaction Details]" class="btn">View Transaction Details</a> --}}
        </div>
        {{-- <div class="footer">
            <p>Need help? Contact our <a href="mailto:support@mycomeso.com">Support Team</a></p>
            <p>Thank you for using MyComeso</p>
        </div> --}}
    </div>
</body>
</html>
