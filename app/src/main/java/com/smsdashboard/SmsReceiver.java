package com.smsdashboard;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
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

public class SmsReceiver extends BroadcastReceiver {

    static final String WEBHOOK = "https://sms.bharatseo.site/api/receive.php";

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

            String time = new SimpleDateFormat("HH:mm:ss", Locale.getDefault()).format(new Date());
            prefs.edit()
                .putString("last_sms_sender", sender)
                .putString("last_sms_time", time)
                .apply();

            if (apiKey.isEmpty()) {
                prefs.edit().putString("last_status", "FAIL: API Key not set").apply();
                return;
            }

            forwardSms(context, apiKey, sender, message);
        }
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
