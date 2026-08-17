import 'package:flutter_test/flutter_test.dart';
import 'package:vidih/main.dart';

void main() {
  testWidgets('shows core video editor features', (tester) async {
    await tester.pumpWidget(const VidihApp());

    expect(find.text('Vidih Studio'), findsOneWidget);
    expect(find.text('Cut video'), findsOneWidget);
    expect(find.text('Merge video'), findsOneWidget);
    expect(find.text('Add text'), findsOneWidget);
    expect(find.text('Add music'), findsOneWidget);
    expect(find.text('Remove sound'), findsOneWidget);
    expect(find.text('Filters'), findsOneWidget);
  });
}
