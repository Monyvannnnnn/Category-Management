<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Bot Connection - Multi-Tenant Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .connect-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            max-width: 520px;
            width: 100%;
            padding: 32px;
        }
        .telegram-icon {
            font-size: 48px;
            color: #0088cc;
        }
        .code-badge {
            background: #0f172a;
            border: 2px dashed #0088cc;
            color: #38bdf8;
            font-family: monospace;
            font-size: 24px;
            letter-spacing: 2px;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
        }
        .pulse-dot {
            height: 10px;
            width: 10px;
            background-color: #f59e0b;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 1.5s infinite ease-in-out;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        .status-connected {
            color: #10b981;
        }
        .btn-telegram {
            background-color: #0088cc;
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 12px;
            transition: all 0.2s ease;
        }
        .btn-telegram:hover {
            background-color: #0077b5;
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="connect-card">
        <div class="text-center mb-4">
            <i class="fa-brands fa-telegram telegram-icon mb-2"></i>
            <h4 class="fw-bold">Telegram Notifications</h4>
            <p class="text-secondary small">Connect your Telegram account to receive real-time inventory updates and command controls.</p>
        </div>

        <!-- STATE 1: NOT CONNECTED / DISCONNECTED -->
        <div id="state-disconnected" class="text-center">
            <button id="btn-connect" class="btn btn-telegram w-100 mb-2" onclick="initiateConnection()">
                <i class="fa-brands fa-telegram me-2"></i> Connect Telegram
            </button>
        </div>

        <!-- STATE 2: PENDING CONNECTION CODE & DEEP LINK -->
        <div id="state-pending" class="d-none">
            <div class="text-center mb-3">
                <span class="pulse-dot me-2"></span>
                <span class="text-warning fw-semibold">Waiting for Telegram Start...</span>
            </div>
            
            <p class="small text-center text-slate-400">Scan or click below to launch Telegram and tap <b>START</b>:</p>
            
            <div class="code-badge mb-3" id="connection-code-display">
                CONNECT-XXXXXX
            </div>

            <a id="telegram-deep-link" href="#" target="_blank" class="btn btn-telegram w-100 mb-3">
                <i class="fa-solid fa-paper-plane me-2"></i> Open Telegram & Press Start
            </a>

            <div class="text-center">
                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> Code expires in 15 minutes</small>
            </div>
        </div>

        <!-- STATE 3: CONNECTED -->
        <div id="state-connected" class="d-none">
            <div class="alert alert-dark border-success text-center mb-3">
                <i class="fa-solid fa-circle-check text-success fa-2x mb-2"></i>
                <h5 class="fw-bold text-success">Connected Successfully!</h5>
                <p class="small text-secondary mb-1">Telegram Chat ID: <b id="chat-id-display" class="text-white">****</b></p>
                <small class="text-muted" id="connected-at-display"></small>
            </div>

            <button class="btn btn-outline-danger w-100" onclick="disconnectTelegram()">
                <i class="fa-solid fa-link-slash me-2"></i> Disconnect Account
            </button>
        </div>

        <!-- SYSTEM LOG / FEEDBACK -->
        <div id="status-message" class="mt-3 text-center small text-secondary"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const USER_ID = 1;
    let pollTimer = null;

    $(document).ready(function() {
        checkCurrentStatus();
    });

    function checkCurrentStatus() {
        $.getJSON('check_connection.php', { user_id: USER_ID }, function(res) {
            if (res.connected) {
                showConnectedState(res.chat_id, res.connected_at);
            } else {
                showDisconnectedState();
            }
        }).fail(function() {
            showDisconnectedState();
        });
    }

    function initiateConnection() {
        $('#btn-connect').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Generating Link...');

        $.getJSON('telegram_connect.php', { user_id: USER_ID }, function(res) {
            $('#btn-connect').prop('disabled', false).html('<i class="fa-brands fa-telegram me-2"></i> Connect Telegram');
            
            if (res.success) {
                $('#connection-code-display').text(res.code);
                $('#telegram-deep-link').attr('href', res.deep_link);
                
                $('#state-disconnected').addClass('d-none');
                $('#state-connected').addClass('d-none');
                $('#state-pending').removeClass('d-none');

                startPolling();
            } else {
                alert("Error: " + (res.error || "Could not generate connection link"));
            }
        }).fail(function(xhr) {
            $('#btn-connect').prop('disabled', false).html('<i class="fa-brands fa-telegram me-2"></i> Connect Telegram');
            alert("Failed to initiate connection. Please check server logs.");
        });
    }

    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);

        pollTimer = setInterval(function() {
            $.getJSON('check_connection.php', { user_id: USER_ID }, function(res) {
                if (res.connected) {
                    clearInterval(pollTimer);
                    showConnectedState(res.chat_id, res.connected_at);
                }
            });
        }, 2000);
    }

    function showConnectedState(chatId, connectedAt) {
        if (pollTimer) clearInterval(pollTimer);
        $('#state-disconnected').addClass('d-none');
        $('#state-pending').addClass('d-none');
        $('#state-connected').removeClass('d-none');

        $('#chat-id-display').text(chatId);
        if (connectedAt) {
            $('#connected-at-display').text("Connected since: " + connectedAt);
        }
    }

    function showDisconnectedState() {
        if (pollTimer) clearInterval(pollTimer);
        $('#state-pending').addClass('d-none');
        $('#state-connected').addClass('d-none');
        $('#state-disconnected').removeClass('d-none');
    }

    function disconnectTelegram() {
        if (!confirm("Are you sure you want to disconnect your Telegram account?")) return;

        $.getJSON('disconnect.php', { user_id: USER_ID }, function(res) {
            if (res.success) {
                showDisconnectedState();
            } else {
                alert("Could not disconnect: " + (res.error || "Unknown error"));
            }
        }).fail(function() {
            showDisconnectedState();
        });
    }
</script>
</body>
</html>
