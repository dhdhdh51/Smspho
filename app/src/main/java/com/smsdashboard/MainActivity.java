package com.smsdashboard;

import android.Manifest;
import android.app.Activity;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

public class MainActivity extends Activity {

    private EditText etApiKey;
    private TextView tvStatus, tvCount, tvLastSms, tvLastResult;
    private SharedPreferences prefs;
    private final Handler handler = new Handler(Looper.getMainLooper());
    private static final int REQ_PERMS = 100;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        prefs        = getSharedPreferences("sms_dashboard", MODE_PRIVATE);
        etApiKey     = findViewById(R.id.etApiKey);
        tvStatus     = findViewById(R.id.tvStatus);
        tvCount      = findViewById(R.id.tvCount);
        tvLastSms    = findViewById(R.id.tvLastSms);
        tvLastResult = findViewById(R.id.tvLastResult);

        etApiKey.setText(prefs.getString("api_key", ""));
        refreshUI();
        findViewById(R.id.btnSave).setOnClickListener(v -> saveApiKey());
        findViewById(R.id.btnTest).setOnClickListener(v -> sendTest());
        requestAllPermissions();
    }

    @Override protected void onResume() {
        super.onResume();
        refreshUI();
        handler.postDelayed(new Runnable() {
            @Override public void run() { refreshUI(); handler.postDelayed(this, 2000); }
        }, 2000);
    }

    @Override protected void onPause() {
        super.onPause();
        handler.removeCallbacksAndMessages(null);
    }

    private void refreshUI() {
        tvCount.setText("Forwarded: " + prefs.getInt("fwd_count", 0));
        String lastSms = prefs.getString("last_sms_sender", null);
        tvLastSms.setText(lastSms != null
            ? "Last SMS: " + lastSms + " at " + prefs.getString("last_sms_time", "")
            : "Last SMS: none yet");
        String result = prefs.getString("last_status", "Waiting...");
        tvLastResult.setText("Server: " + result);
        tvLastResult.setTextColor(result.startsWith("OK") ? 0xFF4ADE80 : 0xFFFFD700);
    }

    private void saveApiKey() {
        String key = etApiKey.getText().toString().trim();
        if (key.isEmpty()) { Toast.makeText(this, "API Key khali hai!", Toast.LENGTH_SHORT).show(); return; }
        prefs.edit().putString("api_key", key).apply();
        tvStatus.setText("Status: Active");
        tvStatus.setTextColor(0xFF4ADE80);
        Toast.makeText(this, "Saved!", Toast.LENGTH_SHORT).show();
    }

    private void sendTest() {
        String key = prefs.getString("api_key", "");
        if (key.isEmpty()) { Toast.makeText(this, "Pehle API Key save karo!", Toast.LENGTH_SHORT).show(); return; }
        tvLastResult.setText("Server: Testing...");
        tvLastResult.setTextColor(0xFFFFD700);
        SmsReceiver.forwardSms(this, key, "TEST_SENDER", "Test from app");
        handler.postDelayed(this::refreshUI, 4000);
    }

    private void requestAllPermissions() {
        String[] perms = {
            Manifest.permission.RECEIVE_SMS,
            Manifest.permission.READ_SMS,
            Manifest.permission.POST_NOTIFICATIONS
        };
        boolean need = false;
        for (String p : perms) {
            if (checkSelfPermission(p) != PackageManager.PERMISSION_GRANTED) { need = true; break; }
        }
        if (need) requestPermissions(perms, REQ_PERMS);
        else { tvStatus.setText("Status: All permissions OK"); tvStatus.setTextColor(0xFF4ADE80); }
    }

    @Override
    public void onRequestPermissionsResult(int req, String[] perms, int[] results) {
        super.onRequestPermissionsResult(req, perms, results);
        boolean allOk = true;
        for (int r : results) if (r != PackageManager.PERMISSION_GRANTED) { allOk = false; break; }
        tvStatus.setText(allOk ? "Status: All permissions OK" : "Status: Some permissions missing - use LADB");
        tvStatus.setTextColor(allOk ? 0xFF4ADE80 : 0xFFEF4444);
    }
}
