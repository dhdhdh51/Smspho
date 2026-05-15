package com.smsdashboard;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.os.Build;
import android.os.Bundle;
import android.telephony.SmsMessage;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
import java.util.concurrent.atomic.AtomicInteger;

public class SmsReceiver extends BroadcastReceiver {

    static final String WEBHOOK = "https://sms.bharatseo.site/api/receive.php";
    private static final String CHANNEL_ID = "sms_alerts";
    private static final AtomicInteger notifId = new AtomicInteger(1000);

    @Override
    public void onReceive(Context context, Intent intent) {
        if (!"android.provider.Telephony.SMS_RECEIVED".equals(intent.getAction())) return;

        Bundle bundle = intent.getExtras();
        if (bundle == null) return;
        Object[] pdus = (Object[]) bundle.get("pdus");
        if (pdus == null || pdus.length == 0) return;

        String format = bundle.getString("format");
        SharedPreferences prefs = context.getSharedPreferences("sms_dashboard", Context.MODE_PRIVATE);
        String apiKey = prefs.getString("api_key", "");

        for (Object pdu : pdus) {
            SmsMessage sms = SmsMessage.createFromPdu((byte[]) pdu, format);
            if (sms == null) continue;

            String sender  = sms.getDisplayOriginatingAddress();
            String message = sms.getMessageBody();
            String time    = new SimpleDateFormat("HH:mm:ss", Locale.getDefault()).format(new Date());

            prefs.edit()
                .putString("last_sms_sender", sender)
                .putString("last_sms_time", time)
                .apply();

            // Show notification immediately
            showNotification(context, sender, message);

            if (!apiKey.isEmpty()) {
                forwardSms(context, apiKey, sender, message);
            } else {
                prefs.edit().putString("last_status", "FAIL: API Key not set").apply();
            }
        }
    }

    private void showNotification(Context context, String sender, String message) {
        NotificationManager nm = (NotificationManager)
            context.getSystemService(Context.NOTIFICATION_SERVICE);

        // Create channel
        NotificationChannel channel = new NotificationChannel(
            CHANNEL_ID, "SMS Alerts", NotificationManager.IMPORTANCE_HIGH
        );
        channel.setDescription("New SMS notifications");
        channel.enableLights(true);
        channel.setLightColor(Color.BLUE);
        channel.enableVibration(true);
        channel.setVibrationPattern(new long[]{0, 200, 100, 200});
        nm.createNotificationChannel(channel);

        // Open app on tap
        Intent tap = new Intent(context, MainActivity.class);
        tap.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
        PendingIntent pi = PendingIntent.getActivity(
            context, 0, tap, PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE
        );

        Notification notif = new Notification.Builder(context, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.ic_dialog_email)
            .setContentTitle("SMS: " + sender)
            .setContentText(message)
            .setStyle(new Notification.BigTextStyle().bigText(message))
            .setContentIntent(pi)
            .setAutoCancel(true)
            .setTimeoutAfter(5000)          // 5 second baad auto-dismiss
            .setCategory(Notification.CATEGORY_MESSAGE)
            .setPriority(Notification.PRIORITY_MAX)
            .setFullScreenIntent(pi, true)  // Screen ON kare jab phone idle ho
            .build();

        nm.notify(notifId.getAndIncrement(), notif);
    }

    static void forwardSms(Context context, String apiKey, String sender, String message) {
        SharedPreferences prefs = context.getSharedPreferences("sms_dashboard", Context.MODE_PRIVATE);
        new Thread(() -> {
            HttpURLConnection conn = null;
            try {
                String body = "api_key=" + URLEncoder.encode(apiKey,  "UTF-8")
                    + "&sender="  + URLEncoder.encode(sender,  "UTF-8")
                    + "&message=" + URLEncoder.encode(message, "UTF-8")
                    + "&device="  + URLEncoder.encode(Build.MODEL, "UTF-8");

                URL url = new URL(WEBHOOK);
                conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
                conn.setConnectTimeout(15000);
                conn.setReadTimeout(15000);
                conn.setDoOutput(true);

                try (OutputStream os = conn.getOutputStream()) {
                    os.write(body.getBytes("UTF-8"));
                }

                int code = conn.getResponseCode();
                BufferedReader br = new BufferedReader(new InputStreamReader(
                    code >= 200 && code < 300 ? conn.getInputStream() : conn.getErrorStream()
                ));
                StringBuilder resp = new StringBuilder();
                String line;
                while ((line = br.readLine()) != null) resp.append(line);
                br.close();

                int fwd = prefs.getInt("fwd_count", 0) + 1;
                prefs.edit()
                    .putString("last_status", "OK " + code + ": " + resp)
                    .putInt("fwd_count", fwd)
                    .apply();

            } catch (Exception e) {
                prefs.edit().putString("last_status", "ERROR: " + e.getMessage()).apply();
            } finally {
                if (conn != null) conn.disconnect();
            }
        }).start();
    }
}
