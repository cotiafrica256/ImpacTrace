import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

const _brandInk = Color(0xFF183B3A);
const _brandTeal = Color(0xFF1C827A);
const _brandGold = Color(0xFFE1A84A);

void main() => runApp(const ImpacTraceApp());

class ApiClient {
  ApiClient._();
  static final instance = ApiClient._();
  final String baseUrl = const String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.0.2.2:8000/api');
  String? token;
  bool reader = false;

  Future<void> restore() async {
    final prefs = await SharedPreferences.getInstance();
    token = prefs.getString('token');
    reader = prefs.getBool('reader') ?? false;
  }

  Future<dynamic> request(String path, {String method = 'GET', Map<String, dynamic>? body, bool public = false}) async {
    final uri = Uri.parse('$baseUrl$path');
    final headers = <String, String>{'Accept': 'application/json', 'Content-Type': 'application/json'};
    if (token != null) headers['Authorization'] = 'Bearer $token';
    final response = method == 'POST'
        ? await http.post(uri, headers: headers, body: jsonEncode(body ?? {}))
        : method == 'PUT'
            ? await http.put(uri, headers: headers, body: jsonEncode(body ?? {}))
            : await http.get(uri, headers: headers);
    final decoded = response.body.isEmpty ? <String, dynamic>{} : jsonDecode(response.body);
    if (response.statusCode >= 400) {
      final message = decoded is Map ? (decoded['message'] ?? decoded['error'] ?? 'Request failed') : 'Request failed';
      throw Exception(message.toString());
    }
    return decoded;
  }

  Future<void> saveSession(String newToken, bool isReader) async {
    token = newToken;
    reader = isReader;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', newToken);
    await prefs.setBool('reader', isReader);
  }

  Future<void> clearSession() async {
    token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    await prefs.remove('reader');
  }
}

class OfflineStore {
  static Future<List<Map<String, dynamic>>> drafts() async {
    final prefs = await SharedPreferences.getInstance();
    return (jsonDecode(prefs.getString('drafts') ?? '[]') as List).cast<Map<String, dynamic>>();
  }

  static Future<void> saveDraft(Map<String, dynamic> draft) async {
    final prefs = await SharedPreferences.getInstance();
    final items = await drafts();
    items.removeWhere((item) => item['id'] == draft['id']);
    items.insert(0, draft);
    await prefs.setString('drafts', jsonEncode(items));
  }
}

class ImpacTraceApp extends StatefulWidget {
  const ImpacTraceApp({super.key});
  @override
  State<ImpacTraceApp> createState() => _ImpacTraceAppState();
}

class _ImpacTraceAppState extends State<ImpacTraceApp> {
  bool ready = false;
  @override
  void initState() { super.initState(); ApiClient.instance.restore().then((_) => setState(() => ready = true)); }
  @override
  Widget build(BuildContext context) => MaterialApp(
    debugShowCheckedModeBanner: false,
    title: 'ImpacTrace',
    theme: ThemeData(useMaterial3: true, colorScheme: ColorScheme.fromSeed(seedColor: _brandTeal, primary: _brandTeal, surface: const Color(0xFFF7F8F4)), scaffoldBackgroundColor: const Color(0xFFF7F8F4), fontFamily: 'sans-serif'),
    home: !ready ? const SplashScreen() : ApiClient.instance.token == null ? const WelcomeScreen() : ApiClient.instance.reader ? const ReaderHome() : const StaffShell(),
  );
}

class SplashScreen extends StatelessWidget { const SplashScreen({super.key}); @override Widget build(BuildContext context) => const Scaffold(body: Center(child: CircularProgressIndicator())); }

class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(body: SafeArea(child: Padding(padding: const EdgeInsets.all(24), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
    const Spacer(),
    Container(width: 58, height: 58, decoration: BoxDecoration(color: _brandTeal, borderRadius: BorderRadius.circular(16)), child: const Icon(Icons.insights_rounded, color: Colors.white, size: 32)),
    const SizedBox(height: 24), const Text('ImpacTrace', style: TextStyle(fontSize: 38, fontWeight: FontWeight.w800, color: _brandInk)),
    const SizedBox(height: 8), const Text('Evidence that moves communities forward.', style: TextStyle(fontSize: 18, color: Colors.black54)),
    const Spacer(),
    _ActionButton(label: 'Explore Knowledge Hub', icon: Icons.menu_book_rounded, onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ReaderHome()))),
    const SizedBox(height: 12), OutlinedButton.icon(onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen(reader: false))), icon: const Icon(Icons.work_outline), label: const Text('Organisation login'), style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(54), foregroundColor: _brandInk)),
    const SizedBox(height: 20), const Center(child: Text('COTIA impact intelligence platform', style: TextStyle(color: Colors.black45))), const SizedBox(height: 12),
  ]))));
}

class _ActionButton extends StatelessWidget { final String label; final IconData icon; final VoidCallback onPressed; const _ActionButton({required this.label, required this.icon, required this.onPressed}); @override Widget build(BuildContext context) => FilledButton.icon(onPressed: onPressed, icon: Icon(icon), label: Text(label), style: FilledButton.styleFrom(backgroundColor: _brandInk, minimumSize: const Size.fromHeight(54), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)))); }

class LoginScreen extends StatefulWidget { final bool reader; const LoginScreen({super.key, required this.reader}); @override State<LoginScreen> createState() => _LoginScreenState(); }
class _LoginScreenState extends State<LoginScreen> {
  final email = TextEditingController(); final password = TextEditingController(); bool busy = false; String? error;
  Future<void> login() async { setState(() { busy = true; error = null; }); try { final result = await ApiClient.instance.request(widget.reader ? '/public/auth/login' : '/login', method: 'POST', body: {'email': email.text.trim(), 'password': password.text}, public: widget.reader); await ApiClient.instance.saveSession(result['token'], widget.reader); if (mounted) Navigator.pushAndRemoveUntil(context, MaterialPageRoute(builder: (_) => widget.reader ? const ReaderHome() : const StaffShell()), (_) => false); } catch (e) { setState(() => error = e.toString().replaceFirst('Exception: ', '')); } finally { if (mounted) setState(() => busy = false); } }
  @override Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: Text(widget.reader ? 'Reader sign in' : 'Organisation login')), body: ListView(padding: const EdgeInsets.all(24), children: [const SizedBox(height: 24), Text(widget.reader ? 'Continue reading' : 'Welcome back', style: const TextStyle(fontSize: 30, fontWeight: FontWeight.w800, color: _brandInk)), const SizedBox(height: 8), Text(widget.reader ? 'Access your purchased research and payment history.' : 'Your role-based operations workspace awaits.', style: const TextStyle(color: Colors.black54)), const SizedBox(height: 32), TextField(controller: email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email', prefixIcon: Icon(Icons.email_outlined))), const SizedBox(height: 16), TextField(controller: password, obscureText: true, decoration: const InputDecoration(labelText: 'Password', prefixIcon: Icon(Icons.lock_outline))), if (error != null) Padding(padding: const EdgeInsets.only(top: 16), child: Text(error!, style: const TextStyle(color: Colors.red))), const SizedBox(height: 28), _ActionButton(label: busy ? 'Signing in...' : 'Sign in', icon: Icons.arrow_forward_rounded, onPressed: busy ? () {} : login), if (widget.reader) TextButton(onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterScreen())), child: const Text('Create a reader account'))]));
}

class RegisterScreen extends StatefulWidget { const RegisterScreen({super.key}); @override State<RegisterScreen> createState() => _RegisterScreenState(); }
class _RegisterScreenState extends State<RegisterScreen> { final name = TextEditingController(), email = TextEditingController(), password = TextEditingController(); bool busy = false; Future<void> register() async { setState(() => busy = true); try { final result = await ApiClient.instance.request('/public/auth/register', method: 'POST', body: {'name': name.text, 'email': email.text, 'password': password.text, 'password_confirmation': password.text}, public: true); await ApiClient.instance.saveSession(result['token'], true); if (mounted) Navigator.pushAndRemoveUntil(context, MaterialPageRoute(builder: (_) => const ReaderHome()), (_) => false); } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString()))); } finally { if (mounted) setState(() => busy = false); } } @override Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('Reader registration')), body: ListView(padding: const EdgeInsets.all(24), children: [const Text('Join the Knowledge Hub', style: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, color: _brandInk)), const SizedBox(height: 24), TextField(controller: name, decoration: const InputDecoration(labelText: 'Full name')), const SizedBox(height: 14), TextField(controller: email, decoration: const InputDecoration(labelText: 'Email')), const SizedBox(height: 14), TextField(controller: password, obscureText: true, decoration: const InputDecoration(labelText: 'Password')), const SizedBox(height: 26), _ActionButton(label: busy ? 'Creating account...' : 'Create account', icon: Icons.person_add_alt_1, onPressed: busy ? () {} : register)])); }

class ReaderHome extends StatefulWidget { const ReaderHome({super.key}); @override State<ReaderHome> createState() => _ReaderHomeState(); }
class _ReaderHomeState extends State<ReaderHome> { List<dynamic> publications = []; bool loading = true; String search = ''; @override void initState() { super.initState(); load(); } Future<void> load() async { try { final result = await ApiClient.instance.request('/public/publications'); setState(() => publications = result is List ? result : (result['data'] ?? [])); } catch (_) {} finally { if (mounted) setState(() => loading = false); } } @override Widget build(BuildContext context) { final filtered = publications.where((item) => '${item['title']} ${item['summary']}'.toLowerCase().contains(search.toLowerCase())).toList(); return Scaffold(appBar: AppBar(title: const Text('Knowledge Hub', style: TextStyle(fontWeight: FontWeight.w800)), actions: [IconButton(onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen(reader: true))), icon: const Icon(Icons.person_outline))]), body: RefreshIndicator(onRefresh: load, child: ListView(padding: const EdgeInsets.fromLTRB(20, 16, 20, 32), children: [const Text('Research, evidence, participation, advocacy.', style: TextStyle(fontSize: 18, color: Colors.black54)), const SizedBox(height: 20), TextField(onChanged: (value) => setState(() => search = value), decoration: InputDecoration(hintText: 'Search publications', prefixIcon: const Icon(Icons.search), filled: true, fillColor: Colors.white, border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none))), const SizedBox(height: 24), const Text('Latest publications', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: _brandInk)), const SizedBox(height: 12), if (loading) const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator())), if (!loading && filtered.isEmpty) const Padding(padding: EdgeInsets.symmetric(vertical: 40), child: Center(child: Text('No publications found.'))), ...filtered.map((publication) => PublicationCard(publication: publication))]))); } }

class PublicationCard extends StatelessWidget { final dynamic publication; const PublicationCard({super.key, required this.publication}); @override Widget build(BuildContext context) { return Card(margin: const EdgeInsets.only(bottom: 14), elevation: 0, clipBehavior: Clip.antiAlias, child: InkWell(onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => PublicationDetail(publication: publication))), child: Padding(padding: const EdgeInsets.all(16), child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [Container(width: 74, height: 92, decoration: BoxDecoration(color: _brandGold.withOpacity(.25), borderRadius: BorderRadius.circular(10)), child: const Icon(Icons.description_outlined, color: _brandInk, size: 30)), const SizedBox(width: 14), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(publication['category'] ?? 'Research', style: const TextStyle(color: _brandTeal, fontWeight: FontWeight.w700)), const SizedBox(height: 5), Text(publication['title'] ?? 'Untitled publication', style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: _brandInk)), const SizedBox(height: 5), Text(publication['summary'] ?? 'Explore this publication.', maxLines: 3, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.black54)), const SizedBox(height: 8), const Text('Read more  ->', style: TextStyle(color: _brandTeal, fontWeight: FontWeight.w700))]))])))); } }

class PublicationDetail extends StatelessWidget { final dynamic publication; const PublicationDetail({super.key, required this.publication}); @override Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('Publication')), body: ListView(padding: const EdgeInsets.all(20), children: [Container(height: 180, decoration: BoxDecoration(color: _brandGold.withOpacity(.25), borderRadius: BorderRadius.circular(18)), child: const Icon(Icons.menu_book_rounded, size: 64, color: _brandInk)), const SizedBox(height: 22), Text(publication['category'] ?? 'Research', style: const TextStyle(color: _brandTeal, fontWeight: FontWeight.w700)), const SizedBox(height: 8), Text(publication['title'] ?? 'Untitled publication', style: const TextStyle(fontSize: 30, fontWeight: FontWeight.w800, color: _brandInk)), const SizedBox(height: 16), Text(publication['summary'] ?? 'No public summary available.', style: const TextStyle(fontSize: 16, height: 1.5)), const SizedBox(height: 26), _ActionButton(label: 'Continue reading', icon: Icons.lock_open_rounded, onPressed: () => ApiClient.instance.token == null ? Navigator.push(context, MaterialPageRoute(builder: (_) => const LoginScreen(reader: true))) : ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Choose an access package from the web checkout or contact support.'))))])); }

class StaffShell extends StatefulWidget { const StaffShell({super.key}); @override State<StaffShell> createState() => _StaffShellState(); }
class _StaffShellState extends State<StaffShell> { int index = 0; String role = 'Staff'; final pages = const [StaffDashboard(), StaffProjects(), StaffSubmissions(), StaffMore()]; @override Widget build(BuildContext context) => Scaffold(body: pages[index], bottomNavigationBar: NavigationBar(selectedIndex: index, onDestinationSelected: (value) => setState(() => index = value), destinations: const [NavigationDestination(icon: Icon(Icons.grid_view_rounded), label: 'Home'), NavigationDestination(icon: Icon(Icons.folder_open_rounded), label: 'Projects'), NavigationDestination(icon: Icon(Icons.fact_check_outlined), label: 'Review'), NavigationDestination(icon: Icon(Icons.more_horiz), label: 'More')])); }

class StaffDashboard extends StatelessWidget { const StaffDashboard({super.key}); @override Widget build(BuildContext context) => _StaffPage(title: 'Good morning', subtitle: 'Your impact workspace at a glance.', children: [Row(children: [Expanded(child: _Metric(label: 'Active projects', value: '4', icon: Icons.folder_open)), const SizedBox(width: 12), Expanded(child: _Metric(label: 'Needs review', value: '12', icon: Icons.pending_actions))]), const SizedBox(height: 24), const Text('Today\'s focus', style: TextStyle(fontSize: 21, fontWeight: FontWeight.w800, color: _brandInk)), const SizedBox(height: 12), _TaskTile(title: 'Complete field submissions', detail: '3 forms saved for sync', icon: Icons.cloud_upload_outlined), _TaskTile(title: 'Review project activity', detail: '2 follow-up actions due', icon: Icons.track_changes_outlined)]); }
class StaffProjects extends StatelessWidget { const StaffProjects({super.key}); @override Widget build(BuildContext context) => _StaffPage(title: 'Projects', subtitle: 'Assigned work and project progress.', children: [const _ProjectTile(name: 'Household climate vulnerability', meta: 'MECPA Uganda  •  Active', progress: .72), const _ProjectTile(name: 'Community resilience plan', meta: 'MECPA Uganda  •  Active', progress: .41)]); }
class StaffSubmissions extends StatelessWidget { const StaffSubmissions({super.key}); @override Widget build(BuildContext context) => _StaffPage(title: 'Submissions', subtitle: 'Review, sync, and follow up on data.', children: [const _TaskTile(title: 'Pending review queue', detail: '12 submissions awaiting action', icon: Icons.fact_check_outlined), const _TaskTile(title: 'Offline queue', detail: '3 saved drafts on this device', icon: Icons.cloud_off_outlined), _ActionButton(label: 'New field submission', icon: Icons.add, onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const FieldSubmissionScreen())))]); }
class StaffMore extends StatelessWidget { const StaffMore({super.key}); @override Widget build(BuildContext context) => _StaffPage(title: 'Workspace', subtitle: 'Tools available for your role.', children: [const _TaskTile(title: 'Reports', detail: 'Generated programme reports', icon: Icons.bar_chart_rounded), const _TaskTile(title: 'Finance', detail: 'Accountability and transactions', icon: Icons.payments_outlined), const _TaskTile(title: 'Help and support', detail: 'Contact the support inbox', icon: Icons.support_agent), _ActionButton(label: 'Sign out', icon: Icons.logout, onPressed: () async { await ApiClient.instance.clearSession(); if (context.mounted) Navigator.pushAndRemoveUntil(context, MaterialPageRoute(builder: (_) => const WelcomeScreen()), (_) => false); })]); }
class _StaffPage extends StatelessWidget { final String title, subtitle; final List<Widget> children; const _StaffPage({required this.title, required this.subtitle, required this.children}); @override Widget build(BuildContext context) => SafeArea(child: ListView(padding: const EdgeInsets.fromLTRB(20, 26, 20, 30), children: [Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: const TextStyle(fontSize: 29, fontWeight: FontWeight.w800, color: _brandInk)), Text(subtitle, style: const TextStyle(color: Colors.black54))]), const CircleAvatar(backgroundColor: _brandGold, child: Icon(Icons.person, color: _brandInk))]), const SizedBox(height: 28), ...children])); }
class _Metric extends StatelessWidget { final String label, value; final IconData icon; const _Metric({required this.label, required this.value, required this.icon}); @override Widget build(BuildContext context) => Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: _brandInk, borderRadius: BorderRadius.circular(16)), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Icon(icon, color: _brandGold), const SizedBox(height: 18), Text(value, style: const TextStyle(fontSize: 30, fontWeight: FontWeight.w800, color: Colors.white)), Text(label, style: const TextStyle(color: Colors.white70))])); }
class _TaskTile extends StatelessWidget { final String title, detail; final IconData icon; const _TaskTile({required this.title, required this.detail, required this.icon}); @override Widget build(BuildContext context) => Card(elevation: 0, margin: const EdgeInsets.only(bottom: 10), child: ListTile(contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6), leading: CircleAvatar(backgroundColor: _brandTeal.withOpacity(.12), child: Icon(icon, color: _brandTeal)), title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700)), subtitle: Text(detail), trailing: const Icon(Icons.chevron_right))); } }
class _ProjectTile extends StatelessWidget { final String name, meta; final double progress; const _ProjectTile({required this.name, required this.meta, required this.progress}); @override Widget build(BuildContext context) => Card(elevation: 0, margin: const EdgeInsets.only(bottom: 12), child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(name, style: const TextStyle(fontWeight: FontWeight.w800, color: _brandInk)), const SizedBox(height: 5), Text(meta, style: const TextStyle(color: Colors.black54)), const SizedBox(height: 16), LinearProgressIndicator(value: progress, color: _brandTeal, minHeight: 8, borderRadius: BorderRadius.circular(8)), const SizedBox(height: 6), Text('${(progress * 100).round()}% progress', style: const TextStyle(color: Colors.black54))]))); }

class FieldSubmissionScreen extends StatefulWidget { const FieldSubmissionScreen({super.key}); @override State<FieldSubmissionScreen> createState() => _FieldSubmissionScreenState(); }
class _FieldSubmissionScreenState extends State<FieldSubmissionScreen> { final form = GlobalKey<FormState>(); final id = TextEditingController(); final name = TextEditingController(); final village = TextEditingController(); bool consent = false; bool saving = false; Future<void> save() async { if (!(form.currentState?.validate() ?? false) || !consent) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Identity and consent are required.'))); return; } setState(() => saving = true); await OfflineStore.saveDraft({'id': DateTime.now().millisecondsSinceEpoch.toString(), 'id_number': id.text, 'name': name.text, 'village': village.text, 'consent': consent, 'saved_at': DateTime.now().toIso8601String()}); if (mounted) { setState(() => saving = false); ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Saved securely to the offline queue.'))); Navigator.pop(context); } } @override Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('New submission')), body: Form(key: form, child: ListView(padding: const EdgeInsets.all(20), children: [const Text('Respondent identity', style: TextStyle(fontSize: 23, fontWeight: FontWeight.w800, color: _brandInk)), const SizedBox(height: 8), const Text('Capture the minimum evidence needed for this project.', style: TextStyle(color: Colors.black54)), const SizedBox(height: 22), TextFormField(controller: id, decoration: const InputDecoration(labelText: 'National ID number', prefixIcon: Icon(Icons.badge_outlined)), validator: (value) => value == null || value.isEmpty ? 'Required' : null), const SizedBox(height: 14), TextFormField(controller: name, decoration: const InputDecoration(labelText: 'Respondent name'), validator: (value) => value == null || value.isEmpty ? 'Required' : null), const SizedBox(height: 14), TextFormField(controller: village, decoration: const InputDecoration(labelText: 'Village / location')), const SizedBox(height: 18), OutlinedButton.icon(onPressed: () {}, icon: const Icon(Icons.camera_alt_outlined), label: const Text('Capture ID photo')), OutlinedButton.icon(onPressed: () {}, icon: const Icon(Icons.edit_outlined), label: const Text('Capture signature')), OutlinedButton.icon(onPressed: () {}, icon: const Icon(Icons.location_on_outlined), label: const Text('Capture GPS location')), const SizedBox(height: 12), CheckboxListTile(value: consent, onChanged: (value) => setState(() => consent = value ?? false), title: const Text('Respondent gave informed consent'), contentPadding: EdgeInsets.zero), const SizedBox(height: 16), _ActionButton(label: saving ? 'Saving...' : 'Save to offline queue', icon: Icons.save_outlined, onPressed: saving ? () {} : save)]))); }
