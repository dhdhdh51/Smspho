package com.smsdashboard.viewer;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.os.Handler;
import android.os.IBinder;
import android.os.Looper;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.concurrent.atomic.AtomicInteger;

public class PollService extends Service {

    private static final String API_URL    = "https://sms.bharatseo.site/api/messages.php";
    private static final String CH_SERVICE = "sms_service";
    private static final String CH_ALERT   = "sms_alert";
    private static final int    POLL_MS    = 8000;   // poll every 8 seconds
    private static final AtomicInteger notifId = new AtomicInteger(2000);

    private Handler handler;
    private SharedPreferences prefs;
    private int lastId = 0;

    private final Runnable pollTask = new Runnable() {
        @Override public void run() {
            pollNewMessages();
            handler.postDelayed(this, POLL_MS);
        }
    };

    @Override
    public void onCreate() {
        super.onCreate();
        handler = new Handler(Looper.getMainLooper());
        prefs   = getSharedPreferences("sms_viewer", MODE_PRIVATE);
        lastId  = prefs.getInt("last_notif_id", 0);
        createChannels();
        startForeground(1, buildServiceNotification());
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        handler.removeCallbacks(pollTask);
        handler.postDelayed(pollTask, 1000);
        return START_STICKY;
    }

    @Override
    public IBinder onBind(Intent i) { return null; }

    @Override
    public void onDestroy() {
        handler.removeCallbacks(pollTask);
        super.onDestroy();
    }

    private void pollNewMessages() {
        String apiKey = prefs.getString("api_key", "");
        if (apiKey.isEmpty()) return;

        new Thread(() -> {
            HttpURLConnection conn = null;
            try {
                String urlStr = API_URL + "?since_id=" + lastId + "&limit=10&api_key=" + apiKey;
                URL url = new URL(urlStr);
                conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setConnectTimeout(10000);
                conn.setReadTimeout(10000);
                conn.setRequestProperty("Accept", "application/json");

                if (conn.getResponseCode() != 200) return;

                BufferedReader br = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                StringBuilder sb = new StringBuilder();
                String line;
                while ((line = br.readLine()) != null) sb.append(line);
                br.close();

                parseAndNotify(sb.toString());
            } catch (Exception ignored) {
            } finally {
                if (conn != null) conn.disconnect();
            }
        }).start();
    }

    private void parseAndNotify(String json) {
        // Simple JSON parse without library
        // Response: {"messages":[{"id":N,"sender":"X","message":"Y","received_at":"Z"},...]}
        if (!json.contains("\"messages\"")) return;

        int msgStart = json.indexOf("[");
        int msgEnd   = json.lastIndexOf("]");
        if (msgStart < 0 || msgEnd < 0) return;

        String arr = json.substring(msgStart + 1, msgEnd);
        if (arr.trim().isEmpty()) return;

        // Split message objects
        String[] objects = arr.split("\\},\\s*\\{");
        for (String obj : objects) {
            try {
                int id = extractInt(obj, "id");
                if (id <= lastId) continue;

                String sender  = extractStr(obj, "sender");
                String message = extractStr(obj, "message");

                showAlert(sender, message);
                if (id > lastId) {
                    lastId = id;
                    prefs.edit().putInt("last_notif_id", lastId).apply();
                }
            } catch (Exception ignored) {}
        }
    }

    private int extractInt(String json, String key) {
        String search = "\"" + key + "\":";
        int i = json.indexOf(search);
        if (i < 0) return 0;
        int start = i + search.length();
        int end   = start;
        while (end < json.length() && (Character.isDigit(json.charAt(end)))) end++;
        return Integer.parseInt(json.substring(start, end));
    }

    private String extractStr(String json, String key) {
        String search = "\"" + key + "\":\"";
        int i = json.indexOf(search);
        if (i < 0) return "";
        int start = i + search.length();
        int end   = json.indexOf("\"", start);
        if (end < 0) return "";
        return json.substring(start, end)
            .replace("\\n", "\n")
            .replace("\\\"", "\"")
            .replace("\\\\", "\\");
    }

    private void showAlert(String sender, String message) {
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);

        Intent tap = new Intent(this, MainActivity.class);
        tap.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
        PendingIntent pi = PendingIntent.getActivity(
            this, 0, tap, PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE
        );

        Notification notif = new Notification.Builder(this, CH_ALERT)
            .setSmallIcon(android.R.drawable.ic_dialog_email)
            .setContentTitle("SMS: " + sender)
            .setContentText(message)
            .setStyle(new Notification.BigTextStyle().bigText(message))
            .setContentIntent(pi)
            .setAutoCancel(true)
            .setTimeoutAfter(5000)
            .setPriority(Notification.PRIORITY_MAX)
            .setCategory(Notification.CATEGORY_MESSAGE)
            .setFullScreenIntent(pi, true)
            .setLights(Color.BLUE, 500, 500)
            .setVibrate(new long[]{0, 200, 100, 200})
            .build();

        nm.notify(notifId.getAndIncrement(), notif);
    }

    private Notification buildServiceNotification() {
        Intent tap = new Intent(this, MainActivity.class);
        PendingIntent pi = PendingIntent.getActivity(
            this, 0, tap, PendingIntent.FLAG_IMMUTABLE
        );
        return new Notification.Builder(this, CH_SERVICE)
            .setSmallIcon(android.R.drawable.ic_dialog_email)
            .setContentTitle("SMS Dashboard")
            .setContentText("Naye messages sun raha hai...")
            .setContentIntent(pi)
            .build();
    }

    private void createChannels() {
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);

        NotificationChannel service = new NotificationChannel(
            CH_SERVICE, "Background Service", NotificationManager.IMPORTANCE_LOW
        );
        service.setDescription("Keeps polling for new SMS");

        NotificationChannel alert = new NotificationChannel(
            CH_ALERT, "SMS Alerts", NotificationManager.IMPORTANCE_HIGH
        );
        alert.setDescription("New SMS notifications");
        alert.enableLights(true);
        alert.setLightColor(Color.BLUE);
        alert.enableVibration(true);

        nm.createNotificationChannel(service);
        nm.createNotificationChannel(alert);
    }
}
