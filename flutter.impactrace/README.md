# ImpacTrace Flutter client

Native mobile client for the ImpacTrace Knowledge Hub and Operations Workspace.

## Run against Railway or local API

```bash
flutter pub get
flutter run --dart-define=API_BASE_URL=https://your-api.example.com/api
```

The default API URL is `http://10.0.2.2:8000/api` for the Android emulator. Use the Railway API URL in `--dart-define` for production builds.

## Build the APK

On a machine with Flutter and the Android SDK installed:

```bash
flutter build apk --release --dart-define=API_BASE_URL=https://your-api.example.com/api
```

The output will be at `build/app/outputs/flutter-apk/app-release.apk`.

The repository also contains `.github/workflows/build-apk.yml`. Push the `flutter.impactrace` folder and run the workflow from GitHub Actions to produce a downloadable APK artifact. Set the repository variable `API_BASE_URL` to the deployed Railway API URL, including `/api`.

## Supported first release flows

- Public reader discovery, search, publication details, reader registration/login, and access status.
- Staff login with role-aware workspace navigation.
- Dashboards for all staff roles, including project/submission/report/finance/support entry points.
- Field officer form capture with respondent identity, consent, GPS, photos, saved drafts, and offline queue storage.
- API requests use the existing Laravel routes in `backend/routes/api.php`.
