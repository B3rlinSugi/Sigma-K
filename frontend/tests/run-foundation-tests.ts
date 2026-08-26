import { runPhase14AFoundationTests } from '../src/services/http/__tests__/foundation.test';

try {
  const success = runPhase14AFoundationTests();
  if (success) {
    console.log('\n✅ PHASE 14A FOUNDATION TEST SUITE: 100% PASS');
    process.exit(0);
  } else {
    console.error('\n❌ PHASE 14A FOUNDATION TEST SUITE: FAILED');
    process.exit(1);
  }
} catch (error) {
  console.error('\n❌ Error executing foundation tests:', error);
  process.exit(1);
}
