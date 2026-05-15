package com.smsdashboard.viewer;

import android.app.Activity;
import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.ClipData;
import android.content.ClipboardManager;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class DashboardActivity extends Activity {

    private static final int    POLL_MS = 5000;
    private static final String CH_ID   = "sms_native";
    private static final AtomicInteger nid = new AtomicInteger(3000);

    private ListView listView;
    private TextView tvEmpty, tvUser, tvStatus;
    private final ArrayList<String> items = new ArrayList<>();
    private ArrayAdapter<String> adapter;
    private final Handler handler = new Handler(Looper.getMainLooper());
    private SharedPreferences prefs;
    private int lastId = 0;
    private boolean firstLoad = true;

    private final Runnable pollTask = new Runnable() {
        @Override public void run() {
            fetchMessages(false);
            handler.postDelayed(this, POLL_MS);
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_dashboard);

        prefs    = getSharedPreferences("sms_viewer", MODE_PRIVATE);
        listView = findViewById(R.id.listView);
        tvEmpty  = findViewById(R.id.tvEmpty);
        tvUser   = findViewById(R.id.tvUser);
        tvStatus = findViewById(R.id.tvStatus);

        tvUser.setText(prefs.getString("name", "Dashboard"));

        adapter = new ArrayAdapter<>(this, R.layout.item_message, R.id.tvContent, items);
        listView.setAdapter(adapter);

        lastId = prefs.getInt("last_id", 0);
        createNotifChannel();
        fetchMessages(true);

        findViewById(R.id.btnLogout).setOnClickListener(v -> logout());
        findViewById(R.id.btnRefresh).setOnClickListener(v -> {
            items.clear();
            adapter.notifyDataSetChanged();
            lastId = 0;
            firstLoad = true;
            setStatus("Refreshing...", "#64748B");
            fetchMessages(true);
        });
    }

    @Override protected void onResume() {
        super.onResume();
        handler.postDelayed(pollTask, POLL_MS);
    }

    @Override protected void onPause() {
        super.onPause();
        handler.removeCallbacks(pollTask);
    }

    private void setStatus(String text, String hexColor) {
        runOnUiThread(() -> {
            tvStatus.setText(text);
            try { tvStatus.setTextColor(Color.parseColor(hexColor)); } catch (Exception ignored) {}
            tvStatus.setVisibility(View.VISIBLE);
        });
    }

    private void fetchMessages(boolean full) {
        String apiKey = prefs.getString("api_key", "");
        if (apiKey.isEmpty()) { logout(); return; }

        int since = full ? 0 : lastId;
        new Thread(() -> {
            try {
                String path = "/api/messages.php?limit=50&since_id=" + since
                    + "&api_key=" + URLEncoder.encode(apiKey, "UTF-8");
                String resp = Api.get(path).trim();

                if (!resp.startsWith("[")) {
                    String errMsg = Api.str(resp, "error");
                    if (errMsg.isEmpty()) errMsg = resp.length() > 120 ? resp.substring(0, 120) + "..." : resp;
                    final String finalErr = errMsg;
                    setStatus("Server error: " + finalErr, "#EF4444");
                    return;
                }

                String inner = resp.substring(1, resp.length() - 1).trim();
                ArrayList<String> newItems = new ArrayList<>();
                int newLastId = lastId;

                if (!inner.isEmpty()) {
                    String[] parts = inner.split("\\},\\s*\\{");
                    for (String part : parts) {
                        int id      = Api.num(part, "id");
                        String from = Api.str(part, "sender");
                        String msg  = Api.str(part, "message");
                        String time = Api.str(part, "received_at");
                        if (id == 0 || from.isEmpty()) continue;
                        if (id > newLastId) newLastId = id;
                        if (!firstLoad && id > lastId) showNotification(from, msg);
                        newItems.add(from + "\n" + msg + "\n" + time);
                    }
                }

                final int finalLastId = newLastId;
                firstLoad = false;

                runOnUiThread(() -> {
                    if (full) { items.clear(); items.addAll(newItems); }
                    else items.addAll(0, newItems);
                    adapter.notifyDataSetChanged();
                    showEmpty(items.isEmpty());
                    lastId = finalLastId;
                    prefs.edit().putInt("last_id", lastId).apply();
                    setStatus("Connected  •  " + items.size() + " messages", "#22C55E");
                });

            } catch (Exception e) {
                String raw = e.getMessage() != null ? e.getMessage() : "Unknown error";
                String jsonErr = Api.str(raw, "error");
                String display = jsonErr.isEmpty() ? raw : jsonErr;
                if (display.length() > 150) display = display.substring(0, 150) + "...";
                setStatus("Error: " + display, "#EF4444");
                // Never auto-logout on network errors
            }
        }).start();
    }

    private void showEmpty(boolean empty) {
        tvEmpty.setVisibility(empty ? View.VISIBLE : View.GONE);
        listView.setVisibility(empty ? View.GONE : View.VISIBLE);
    }

    private static String extractOtp(String message) {
        Pattern p = Pattern.compile("\\b([0-9]{4,8})\\b");
        Matcher m = p.matcher(message);
        String best = null;
        while (m.find()) {
            String found = m.group(1);
            if (best == null || found.length() == 6) best = found;
        }
        return best;
    }

    private void showNotification(String sender, String message) {
        String otp = extractOtp(message);
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
        Intent tap = new Intent(this, DashboardActivity.class);
        tap.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
        PendingIntent pi = PendingIntent.getActivity(this, 0, tap,
            PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);

        String title   = otp != null ? "OTP: " + otp + "  (" + sender + ")" : "SMS: " + sender;
        String bigText = otp != null ? "OTP: " + otp + "\n\n" + message : message;

        Notification n = new Notification.Builder(this, CH_ID)
            .setSmallIcon(android.R.drawable.ic_dialog_email)
            .setContentTitle(title)
            .setContentText(otp != null ? otp : message)
            .setStyle(new Notification.BigTextStyle().bigText(bigText))
            .setContentIntent(pi)
            .setAutoCancel(true)
            .setTimeoutAfter(5000)
            .setPriority(Notification.PRIORITY_MAX)
            .setCategory(Notification.CATEGORY_MESSAGE)
            .setFullScreenIntent(pi, true)
            .setVibrate(new long[]{0, 200, 100, 200})
            .setLights(Color.BLUE, 500, 500)
            .build();

        nm.notify(nid.getAndIncrement(), n);

        if (otp != null) {
            runOnUiThread(() -> {
                ClipboardManager cm = (ClipboardManager) getSystemService(CLIPBOARD_SERVICE);
                cm.setPrimaryClip(ClipData.newPlainText("OTP", otp));
                Toast.makeText(this, "OTP copied: " + otp, Toast.LENGTH_SHORT).show();
            });
        }
    }

    private void createNotifChannel() {
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
        NotificationChannel ch = new NotificationChannel(CH_ID, "SMS Alerts", NotificationManager.IMPORTANCE_HIGH);
        ch.enableLights(true);
        ch.setLightColor(Color.BLUE);
        ch.enableVibration(true);
        nm.createNotificationChannel(ch);
    }

    private void logout() {
        handler.removeCallbacks(pollTask);
        prefs.edit().clear().apply();
        startActivity(new Intent(this, LoginActivity.class));
        finish();
    }
}
