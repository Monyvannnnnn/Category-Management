/**
 * Main Application JavaScript (app.js)
 * Inventory & Category Management System
 */

// Global LoadingOverlay fallback object to prevent ReferenceErrors
window.LoadingOverlay = window.LoadingOverlay || {
    show: function (msg) {},
    hide: function () {}
};

// Helper for push report card loading states
function setCardLoading($btn, isLoading, statusText) {
    if (!$btn || !$btn.length) return;
    if (isLoading) {
        if (!$btn.data("orig-html")) {
            $btn.data("orig-html", $btn.html());
        }
        $btn.addClass("is-loading btn-loading").prop("disabled", true);
        var $arrow = $btn.find(".report-arrow");
        if ($arrow.length) {
            $arrow.removeClass("fa-chevron-right").addClass("fa-spinner fa-spin").css({ "color": "#38bdf8", "font-size": "16px" });
        }
        if (statusText) {
            $btn.find(".report-desc").text(statusText);
        }
    } else {
        var origHtml = $btn.data("orig-html");
        if (origHtml) {
            $btn.html(origHtml);
            $btn.removeData("orig-html");
        }
        $btn.removeClass("is-loading btn-loading").prop("disabled", false);
    }
}
window.setCardLoading = setCardLoading;

$(document).ready(function () {
    console.log("Inventory App Initialized");
});

// Global AJAX error handler to automatically redirect unauthenticated users to login.php
$(document).ajaxError(function (event, jqXHR) {
    if (jqXHR && jqXHR.status === 401) {
        window.location.href = "login.php";
    }
});

// Helper: Format DateTime to dd/MM/yyyy HH:mm:ss
function formatDateTime(date) {
    if (!date) return "-";
    const d = new Date(date);
    if (isNaN(d.getTime())) return date;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const mins = String(d.getMinutes()).padStart(2, '0');
    const secs = String(d.getSeconds()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${mins}:${secs}`;
}

// Helper: Calculate Relative Time (Time Ago)
function timeAgo(date) {
    if (!date) return "";
    const d = new Date(date);
    if (isNaN(d.getTime())) return "";
    const seconds = Math.floor((new Date() - d) / 1000);

    if (seconds < 60) return "Just now";
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} min${minutes > 1 ? 's' : ''} ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days} day${days > 1 ? 's' : ''} ago`;
    const months = Math.floor(days / 30);
    if (months < 12) return `${months} month${months > 1 ? 's' : ''} ago`;
    const years = Math.floor(months / 12);
    return `${years} year${years > 1 ? 's' : ''} ago`;
}

/**
 * Global Custom Modern Glassmorphic Confirm Dialog
 */
function showCustomConfirmDialog(options) {
    options = options || {};
    var title = options.title || "Confirmation";
    var message = options.message || "Are you sure you want to proceed?";
    var confirmText = options.confirmText || "Confirm";
    var cancelText = options.cancelText || "Cancel";
    var confirmBg = options.confirmBg || "linear-gradient(135deg, #ef4444, #dc2626)";
    var icon = options.icon || "fa-triangle-exclamation";
    var iconColor = options.iconColor || "#ef4444";

    var $overlay = $("<div>", {
        class: "custom-confirm-backdrop",
        css: {
            position: "fixed",
            top: 0,
            left: 0,
            width: "100vw",
            height: "100vh",
            background: "rgba(15, 23, 42, 0.8)",
            backdropFilter: "blur(8px)",
            webkitBackdropFilter: "blur(8px)",
            zIndex: 999999,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            opacity: 0,
            transition: "opacity 0.2s ease"
        }
    });

    var $dialog = $("<div>", {
        class: "custom-confirm-card",
        css: {
            background: "#1e293b",
            border: "1px solid rgba(255, 255, 255, 0.15)",
            borderRadius: "16px",
            boxShadow: "0 25px 50px -12px rgba(0, 0, 0, 0.75)",
            width: "90%",
            maxWidth: "420px",
            padding: "24px",
            textAlign: "center",
            transform: "scale(0.92)",
            transition: "transform 0.2s cubic-bezier(0.16, 1, 0.3, 1)"
        }
    });

    var html = '<div style="width: 54px; height: 54px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: ' + iconColor + '; font-size: 22px;">' +
        '<i class="fa-solid ' + icon + '"></i>' +
        '</div>' +
        '<h4 style="color: #f8fafc; font-size: 17px; font-weight: 700; margin: 0 0 8px 0; font-family: system-ui, -apple-system, sans-serif;">' + title + '</h4>' +
        '<p style="color: #94a3b8; font-size: 13.5px; margin: 0 0 22px 0; line-height: 1.5; font-family: system-ui, -apple-system, sans-serif;">' + message + '</p>' +
        '<div style="display: flex; gap: 10px; justify-content: center;">' +
        '<button type="button" class="btn-confirm-cancel" style="flex: 1; background: rgba(255, 255, 255, 0.08); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.15); padding: 10px 16px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">' +
        cancelText +
        '</button>' +
        '<button type="button" class="btn-confirm-accept" style="flex: 1; background: ' + confirmBg + '; color: #ffffff; border: none; padding: 10px 16px; border-radius: 9px; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35); transition: all 0.2s;">' +
        confirmText +
        '</button>' +
        '</div>';

    $dialog.html(html);
    $overlay.append($dialog);
    $("body").append($overlay);

    requestAnimationFrame(function() {
        $overlay.css("opacity", 1);
        $dialog.css("transform", "scale(1)");
    });

    function close() {
        $overlay.css("opacity", 0);
        $dialog.css("transform", "scale(0.92)");
        setTimeout(function() {
            $overlay.remove();
        }, 200);
    }

    $dialog.find(".btn-confirm-cancel").on("click", function() {
        close();
    });

    $dialog.find(".btn-confirm-accept").on("click", function() {
        close();
        if (typeof options.onConfirm === "function") {
            options.onConfirm();
        }
    });

    $overlay.on("click", function(e) {
        if ($(e.target).is($overlay)) {
            close();
        }
    });
}
window.showCustomConfirmDialog = showCustomConfirmDialog;
