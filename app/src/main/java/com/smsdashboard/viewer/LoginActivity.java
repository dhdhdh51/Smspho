package com.smsdashboard.viewer;

import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

public class LoginActivity extends Activity {

    private EditText etEmail, etPass;
    private Button btnLogin;
    private ProgressBar progress;
    private TextView tvServer;
    private SharedPreferences prefs;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        prefs     = getSharedPreferences("sms_viewer", MODE_PRIVATE);
        etEmail   = findViewById(R.id.etEmail);
        etPass    = findViewById(R.id.etPass);
        btnLogin  = findViewById(R.id.btnLogin);
        progress  = findViewById(R.id.progress);
        tvServer  = findViewById(R.id.tvServer);

        tvServer.setText("Server: " + Api.BASE);

        // Already logged in
        if (!prefs.getString("api_key", "").isEmpty()) {
            goToDashboard();
            return;
        }

        btnLogin.setOnClickListener(v -> doLogin());
    }

    private void doLogin() {
        String email = etEmail.getText().toString().trim();
        String pass  = etPass.getText().toString();
        if (email.isEmpty() || pass.isEmpty()) {
            Toast.makeText(this, "Email aur password daalo", Toast.LENGTH_SHORT).show();
            return;
        }

        btnLogin.setEnabled(false);
        progress.setVisibility(View.VISIBLE);

        new Thread(() -> {
            try {
                String body = "{\"email\":\"" + email + "\",\"password\":\"" + pass + "\"}";
                String resp = Api.post("/api/login.php", body);
                String apiKey = Api.str(resp, "api_key");
                String name   = Api.str(resp, "name");

                if (apiKey.isEmpty()) {
                    throw new Exception("Server ne api_key nahi diya. Response: " + resp);
                }

                runOnUiThread(() -> {
                    prefs.edit()
                        .putString("api_key", apiKey)
                        .putString("name", name)
                        .putInt("last_id", 0)
                        .apply();
                    goToDashboard();
                });
            } catch (Exception e) {
                String msg = e.getMessage() != null ? e.getMessage() : "Unknown error";
                // Extract clean error from JSON if possible
                String jsonErr = Api.str(msg, "error");
                String display = jsonErr.isEmpty() ? msg : jsonErr;
                runOnUiThread(() -> {
                    progress.setVisibility(View.GONE);
                    btnLogin.setEnabled(true);
                    Toast.makeText(this, display, Toast.LENGTH_LONG).show();
                });
            }
        }).start();
    }

    private void goToDashboard() {
        startActivity(new Intent(this, DashboardActivity.class));
        finish();
    }
}
