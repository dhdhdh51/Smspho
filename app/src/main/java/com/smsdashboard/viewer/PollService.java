package com.smsdashboard.viewer;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.ClipData;
import android.content.ClipboardManager;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.os.Handler;
import android.os.IBinder;
import android.os.Looper;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.atomic.AtomicBoolean;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class PollService extends Service {

    private static final int    FG_NOTIF_ID = 1;
    private static final int    POLL_MS     = 2000;
    private static final String FG_CH       = "sms_fg";
    private static final String MSG_CH      = "sms_native";
    private static final AtomicInteger nid  = new AtomicInteger(2000);

    private Handler           handler;
    private SharedPreferences prefs;
    private int               lastId      = 0;
    private boolean           initialized = false; // silent on first poll if lastId==0
    private final AtomicBoolean polling   = new AtomicBoolean(false);

    private final Runnable pollTask = new Runnable() {
        @Override public void run() {
            doPoll();
            handler.postDelayed(this, POLL_MS);
        }
    };

    @Override
    public void onCreate() {
        super.onCreate();
        handler = new Handler(Looper.getMainLooper());
        prefs   = getSharedPreferences("sms_viewer", MODE_PRIVATE);
        createChannels();
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        lastId      = prefs.getInt("last_id", 0);
        initialized = lastId > 0; // already have a known position → notify immediately
        startForeground(FG_NOTIF_ID, buildForegroundNotif());
        handler.removeCallbacks(pollTask);
        handler.post(pollTask);
        return START_STICKY;
    }

    @Override
    public void onDestroy() {
        handler.removeCallbacks(pollTask);
        super.onDestroy();
    }

    @Override public IBinder onBind(Intent i) { return null; }

    private void doPoll() {
        if (!polling.compareAndSet(false, true)) return;
        String apiKey = prefs.getString("api_key", "");
        if (apiKey.isEmpty()) { stopSelf(); polling.set(false); return; }

        final boolean notifyThisRound = initialized;

        new Thread(() -> {
            try {
                String path = "/api/messages.php?limit=20&since_id=" + lastId
                    + "&api_key=" + URLEncoder.encode(apiKey, "UTF-8");
                String resp = Api.get(path).trim();

                String inner = extractInner(resp);
                if (inner == null || inner.isEmpty()) return;

                List<String> objects = extractObjects(inner);
                for (String obj : objects) {
                    int    id   = Api.num(obj, "id");
                    String from = Api.str(obj, "sender");
                    String msg  = Api.str(obj, "message");
                    if (id == 0 || from.isEmpty()) continue;
                    if (id > lastId) {
                        lastId = id;
                        prefs.edit().putInt("last_id", lastId).apply();
                        if (notifyThisRound) {
                            showMsgNotification(from, msg);
                        }
                    }
                }
            } catch (Exception ignored) {
            } finally {
                initialized = true; // from second poll onwards, always notify
                polling.set(false);
            }
        }).start();
    }

    // ── JSON helpers ──────────────────────────────────────────────────────────

    private String extractInner(String resp) {
        if (resp.startsWith("[")) {
            return resp.substring(1, resp.length() - 1).trim();
        } else if (resp.startsWith("{")) {
            int arrMark = resp.indexOf("\"messages\":[");
            if (arrMark < 0) return null;
            int s = resp.indexOf("[", arrMark) + 1;
            int depth = 1, p = s;
            boolean inStr = false, esc = false;
            while (p < resp.length() && depth > 0) {
                char c = resp.charAt(p);
                if (esc)              { esc = false; }
                else if (c == '\\' && inStr) { esc = true; }
                else if (c == '"')    { inStr = !inStr; }
                else if (!inStr) {
                    if (c == '[') depth++;
                    else if (c == ']') depth--;
                }
                p++;
            }
            return resp.substring(s, p - 1).trim();
        }
        return null;
    }

    static List<String> extractObjects(String inner) {
        List<String> result = new ArrayList<>();
        int depth = 0, start = -1;
        boolean inStr = false, escape = false;
        for (int i = 0; i < inner.length(); i++) {
            char c = inner.charAt(i);
            if (escape)             { escape = false; continue; }
            if (c == '\\' && inStr) { escape = true;  continue; }
            if (c == '"')           { inStr = !inStr;  continue; }
            if (inStr) continue;
            if (c == '{') { if (depth == 0) start = i; depth++; }
            else if (c == '}') {
                depth--;
                if (depth == 0 && start >= 0) { result.add(inner.substring(start, i + 1)); start = -1; }
            }
        }
        return result;
    }

    private static String extractOtp(String msg) {
        Pattern p = Pattern.compile("\\b([0-9]{4,8})\\b");
        Matcher m = p.matcher(msg);
        String best = null;
        while (m.find()) {
            String found = m.group(1);
            if (best == null || found.length() == 6) best = found;
        }
        return best;
    }

    private void showMsgNotification(String sender, String message) {
        String otp = extractOtp(message);
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);

        Intent tap = new Intent(this, MainActivity.class);
        tap.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
        PendingIntent pi = PendingIntent.getActivity(this, nid.get(), tap,
            PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);

        String title   = otp != null ? "OTP: " + otp + "  (" + sender + ")" : "SMS: " + sender;
        String bigText = otp != null ? "OTP: " + otp + "\n\n" + message : message;

        Notification n = new Notification.Builder(this, MSG_CH)
            .setSmallIcon(android.R.drawable.ic_dialog_email)
            .setContentTitle(title)
            .setContentText(otp != null ? otp : message)
            .setStyle(new Notification.BigTextStyle().bigText(bigText))
            .setContentIntent(pi)
            .setAutoCancel(true)
            .setTimeoutAfter(20000)
            .setPriority(Notification.PRIORITY_MAX)
            .setCategory(Notification.CATEGORY_MESSAGE)
            .setFullScreenIntent(pi, true)
            .setVibrate(new long[]{0, 200, 100, 200})
            .setLights(Color.BLUE, 500, 500)
            .build();

        nm.notify(nid.getAndIncrement(), n);

        // Copy OTP to clipboard
        if (otp != null) {
            ClipboardManager cm = (ClipboardManager) getSystemService(CLIPBOARD_SERVICE);
            cm.setPrimaryClip(ClipData.newPlainText("OTP", otp));
        }
    }

    private Notification buildForegroundNotif() {
        Intent tap = new Intent(this, MainActivity.class);
        PendingIntent pi = PendingIntent.getActivity(this, 0, tap,
            PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);
        return new Notification.Builder(this, FG_CH)
            .setSmallIcon(android.R.drawable.ic_dialog_email)
            .setContentTitle("SMS Dashboard")
            .setContentText("Nayi SMS ka wait kar raha hai...")
            .setContentIntent(pi)
            .setOngoing(true)
            .build();
    }

    private void createChannels() {
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
        NotificationChannel fg = new NotificationChannel(FG_CH, "SMS Monitor", NotificationManager.IMPORTANCE_LOW);
        nm.createNotificationChannel(fg);
        NotificationChannel msg = new NotificationChannel(MSG_CH, "SMS Alerts", NotificationManager.IMPORTANCE_HIGH);
        msg.enableLights(true);
        msg.setLightColor(Color.BLUE);
        msg.enableVibration(true);
        nm.createNotificationChannel(msg);
    }
}
