<?php
// fieldbi.php - Instant Direct Redirect to Field BI App for Fast Native Rendering & Chart GPU Acceleration
$targetUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";

// HTTP 302 Instant Server Redirect
header("Location: " . $targetUrl, true, 302);
header("Cache-Control: no-cache, must-revalidate");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($targetUrl); ?>">
    <title>Redirecting to Field BI...</title>
    <script>
        window.location.replace("<?php echo $targetUrl; ?>");
    </script>
</head>
<body style="background:#0f141c; color:#94a3b8; font-family:sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; margin:0;">
    <p>Opening Field BI App... <a href="<?php echo htmlspecialchars($targetUrl); ?>" style="color:#10b981;">Click here if not redirected</a></p>
</body>
</html>
