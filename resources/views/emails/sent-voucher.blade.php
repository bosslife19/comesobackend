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
            <h1>Money Sent Successfully</h1>
        </div>
        <div class="content">
            <h2>Hi {{$name}},</h2>
            <p>
                Your money transfer to <strong>{{$receiver}}</strong> has been processed successfully. Below are the transaction details for your reference:
            </p>
            <div class="transaction-box">
                <p><strong>Amount Sent:</strong> GH₵{{$amount}}</p>
                <p><strong>Recipient:</strong>{{$receiver}}</p>
                <p><strong>Date:</strong>{{$date}}</p>
                <p><strong>Transaction Id:</strong>{{$transId}}</p>
            </div>
            <p>
                Thank you for using MyComeso to manage your transactions. If you have any questions, feel free to contact our support team.
            </p>
            <a href="[Link to Transaction Details]" class="btn">View Transaction Details</a>
        </div>
        <div class="footer">
            <p>Need help? Contact our <a href="mailto:support@example.com">Support Team</a></p>
            <p>Thank you for choosing COMESO</p>
        </div>
    </div>
</body>
</html>
