package com.smsdashboard.viewer;

import android.app.Activity;
import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
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

public class DashboardActivity extends Activity {

    private static final int    POLL_MS = 5000;
    private static final String CH_ID   = "sms_native";
    private static final AtomicInteger nid = new AtomicInteger(3000);

    private ListView listView;
    private TextView tvEmpty, tvUser;
    private final ArrayList<String> items    = new ArrayList<>();
    private final ArrayList<int[]>  msgIds   = new ArrayList<>();
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

        String name = prefs.getString("name", "Dashboard");
        tvUser.setText(name);

        adapter = new ArrayAdapter<>(this, R.layout.item_message, R.id.tvContent, items);
        listView.setAdapter(adapter);

        lastId = prefs.getInt("last_id", 0);
        createNotifChannel();
        fetchMessages(true);

        findViewById(R.id.btnLogout).setOnClickListener(v -> logout());
        findViewById(R.id.btnRefresh).setOnClickListener(v -> fetchMessages(true));
    }

    @Override protected void onResume() {
        super.onResume();
        handler.postDelayed(pollTask, POLL_MS);
    }

    @Override protected void onPause() {
        super.onPause();
        handler.removeCallbacks(pollTask);
    }

    private void fetchMessages(boolean full) {
        String apiKey = prefs.getString("api_key", "");
        if (apiKey.isEmpty()) { logout(); return; }

        int since = full ? 0 : lastId;
        new Thread(() -> {
            try {
                String path = "/api/messages.php?limit=50&since_id=" + since
                    + "&api_key=" + URLEncoder.encode(apiKey, "UTF-8");
                String resp = Api.get(path);

                // Parse messages array
                int arrStart = resp.indexOf("[");
                int arrEnd   = resp.lastIndexOf("]");
                if (arrStart < 0 || arrEnd <= arrStart) {
                    runOnUiThread(() -> showEmpty(true));
                    return;
                }

                String arr = resp.substring(arrStart + 1, arrEnd).trim();
                if (arr.isEmpty()) {
                    if (full) runOnUiThread(() -> showEmpty(items.isEmpty()));
                    return;
                }

                String[] parts = arr.split("\\},\\s*\\{");
                ArrayList<String> newItems = new ArrayList<>();
                int newLastId = lastId;

                for (String part : parts) {
                    int id      = Api.num(part, "id");
                    String from = Api.str(part, "sender");
                    String msg  = Api.str(part, "message");
                    String time = Api.str(part, "received_at");
                    if (id == 0) continue;
                    if (id > newLastId) newLastId = id;

                    String display = from + "\n" + msg + "\n" + time;
                    newItems.add(0, display);

                    // Notify if new message (not first load)
                    if (!firstLoad && id > lastId) {
                        showNotification(from, msg);
                    }
                }

                final int finalLastId = newLastId;
                final boolean wasFirstLoad = firstLoad;
                firstLoad = false;

                runOnUiThread(() -> {
                    if (full) { items.clear(); items.addAll(newItems); }
                    else items.addAll(0, newItems);

                    adapter.notifyDataSetChanged();
                    showEmpty(items.isEmpty());
                    lastId = finalLastId;
                    prefs.edit().putInt("last_id", lastId).apply();
                });

            } catch (Exception e) {
                if (full) runOnUiThread(() ->
                    Toast.makeText(this, "Error: " + e.getMessage(), Toast.LENGTH_SHORT).show()
                );
            }
        }).start();
    }

    private void showEmpty(boolean empty) {
        tvEmpty.setVisibility(empty ? View.VISIBLE : View.GONE);
        listView.setVisibility(empty ? View.GONE : View.VISIBLE);
    }

    private void showNotification(String sender, String message) {
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
        Intent tap = new Intent(this, DashboardActivity.class);
        tap.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
        PendingIntent pi = PendingIntent.getActivity(this, 0, tap,
            PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);

        Notification n = new Notification.Builder(this, CH_ID)
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
            .setVibrate(new long[]{0, 200, 100, 200})
            .setLights(Color.BLUE, 500, 500)
            .build();

        nm.notify(nid.getAndIncrement(), n);
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
        prefs.edit().clear().apply();
        startActivity(new Intent(this, LoginActivity.class));
        finish();
    }
}
