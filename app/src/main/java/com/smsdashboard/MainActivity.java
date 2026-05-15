package com.smsdashboard;

import android.Manifest;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.os.Bundle;
import android.view.View;
import android.widget.*;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

public class MainActivity extends AppCompatActivity {

    private EditText etApiKey;
    private TextView tvStatus, tvCount;
    private SharedPreferences prefs;

    private static final int REQ_SMS = 100;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        prefs    = getSharedPreferences("sms_dashboard", MODE_PRIVATE);
        etApiKey = findViewById(R.id.etApiKey);
        tvStatus = findViewById(R.id.tvStatus);
        tvCount  = findViewById(R.id.tvCount);

        // Load saved API key
        etApiKey.setText(prefs.getString("api_key", ""));
        tvCount.setText("Forwarded: " + prefs.getInt("fwd_count", 0));

        findViewById(R.id.btnSave).setOnClickListener(v -> saveApiKey());
        requestSmsPermission();
    }

    private void saveApiKey() {
        String key = etApiKey.getText().toString().trim();
        if (key.isEmpty()) {
            toast("API Key khali hai!");
            return;
        }
        prefs.edit().putString("api_key", key).apply();
        tvStatus.setText("Status: Active — SMS forward ho raha hai");
        tvStatus.setTextColor(0xFF4ADE80);
        toast("API Key save ho gaya!");
    }

    private void requestSmsPermission() {
        String[] perms = {
            Manifest.permission.RECEIVE_SMS,
            Manifest.permission.READ_SMS
        };
        boolean needRequest = false;
        for (String p : perms) {
            if (ContextCompat.checkSelfPermission(this, p) != PackageManager.PERMISSION_GRANTED) {
                needRequest = true;
                break;
            }
        }
        if (needRequest) {
            ActivityCompat.requestPermissions(this, perms, REQ_SMS);
        } else {
            tvStatus.setText("Status: Active — SMS sun raha hai");
            tvStatus.setTextColor(0xFF4ADE80);
        }
    }

    @Override
    public void onRequestPermissionsResult(int req, String[] perms, int[] results) {
        super.onRequestPermissionsResult(req, perms, results);
        boolean granted = results.length > 0 && results[0] == PackageManager.PERMISSION_GRANTED;
        tvStatus.setText(granted
            ? "Status: Active — SMS sun raha hai"
            : "Status: Permission denied — SMS nahi aayega");
        tvStatus.setTextColor(granted ? 0xFF4ADE80 : 0xFFEF4444);
    }

    private void toast(String msg) {
        Toast.makeText(this, msg, Toast.LENGTH_SHORT).show();
    }
}
