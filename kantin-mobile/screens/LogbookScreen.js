import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, StatusBar, ScrollView, Platform, TouchableOpacity, Alert, ActivityIndicator, Modal } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export default function LogbookScreen({ navigation }) {
  const [data, setData] = useState({ pendapatan_kotor: 0, laba_bersih: 0, history: [] });
  const [loading, setLoading] = useState(true);
  const [selectedNota, setSelectedNota] = useState(null);

  // URL SUDAH DISESUAIKAN DENGAN NODE.JS
  // Untuk file lainnya (Dashboard, Kasir, Admin, Logbook):
  const BASE_URL = "http://10.234.56.211:3000/api";

  useEffect(() => { fetchLogbook(); }, []);

  const fetchLogbook = async () => { setLoading(true); try { const response = await fetch(`${BASE_URL}/logbook`); const result = await response.json(); if (result.success) setData(result); } catch (error) { console.log(error); } finally { setLoading(false); } };
  const handleReset = () => { Alert.alert("Reset Logbook", "Yakin ingin menghapus SELURUH riwayat transaksi?", [{ text: "Batal", style: "cancel" }, { text: "Ya, Hapus", style: "destructive", onPress: async () => { await fetch(`${BASE_URL}/reset_logbook`); fetchLogbook(); Alert.alert("Sukses", "Riwayat berhasil di-reset."); } }]); };
  const formatTanggal = (tglString) => { const tgl = new Date(tglString); return tgl.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).replace(/\./g, ':'); };
  
  const handleLogout = () => { Alert.alert("Logout", "Yakin ingin berlabuh (keluar)?", [{ text: "Batal", style: "cancel" }, { text: "Logout", style: "destructive", onPress: () => navigation.replace('Login') }]); };

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar backgroundColor="#0F172A" barStyle="light-content" />
      
      {/* GLOBAL HEADER */}
      <View style={styles.globalHeader}>
        <View style={styles.headerLeft}>
          <Ionicons name="water" size={18} color="#FFFFFF" />
          <Text style={styles.headerLogo}>KANTIN PERKAPALAN</Text>
        </View>
        <View style={styles.headerRight}>
          <View style={styles.userInfo}>
            <Ionicons name="person-circle-outline" size={16} color="#FFFFFF" />
            <Text style={styles.userName}>Kapten <Text style={styles.userRole}>admin</Text></Text>
          </View>
          <TouchableOpacity style={styles.btnLogoutGlobal} onPress={handleLogout}>
            <Text style={styles.btnLogoutGlobalText}>Berlabuh (Logout)</Text>
          </TouchableOpacity>
        </View>
      </View>

      <View style={styles.mainContainer}>
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          {loading ? <ActivityIndicator size="large" color="#0284C7" style={{marginTop: 50}} /> : (
            <>
              <View style={styles.cardContainer}>
                <View style={[styles.financeCard, { backgroundColor: '#38BDF8' }]}>
                  <View style={styles.cardHeaderRow}><Ionicons name="wallet-outline" size={16} color="rgba(255,255,255,0.8)" /><Text style={styles.financeLabel}> Pendapatan Kotor Hari Ini</Text></View>
                  <Text style={styles.financeValue}>Rp {data.pendapatan_kotor}</Text>
                  <Ionicons name="cash" size={40} color="rgba(255,255,255,0.2)" style={styles.cardIconBg} />
                </View>
                <View style={[styles.financeCard, { backgroundColor: '#22C55E' }]}>
                  <View style={styles.cardHeaderRow}><Ionicons name="trending-up-outline" size={16} color="rgba(255,255,255,0.8)" /><Text style={styles.financeLabel}> Laba Bersih Hari Ini</Text></View>
                  <Text style={styles.financeValue}>Rp {data.laba_bersih}</Text>
                  <Ionicons name="leaf" size={40} color="rgba(255,255,255,0.2)" style={styles.cardIconBg} />
                </View>
              </View>

              <View style={styles.tableContainer}>
                <View style={styles.tableHeaderSection}>
                  <View>
                    <View style={{flexDirection: 'row', alignItems: 'center'}}><Ionicons name="checkbox-outline" size={20} color="#FACC15" /><Text style={styles.sectionTitle}> History Transaksi</Text></View>
                    <Text style={styles.sectionSubtitle}>Klik nama pembeli untuk melihat Nota</Text>
                  </View>
                  <TouchableOpacity style={styles.btnReset} onPress={handleReset}><Ionicons name="trash" size={14} color="white" /><Text style={styles.btnResetText}>Reset</Text></TouchableOpacity>
                </View>
                <View style={styles.tableHeader}>
                  <Text style={[styles.th, { flex: 0.6 }]}>Resi</Text><Text style={[styles.th, { flex: 1.5 }]}>Pembeli & Waktu</Text><Text style={[styles.th, { flex: 1.2 }]}>Tagihan</Text><Text style={[styles.th, { flex: 1, textAlign: 'center' }]}>Status</Text>
                </View>
                {data.history.map((item) => (
                  <View key={item.id} style={styles.tableRow}>
                    <Text style={[styles.td, { flex: 0.6, color: '#64748B', fontWeight: 'bold' }]}>#{item.id}</Text>
                    <TouchableOpacity style={{ flex: 1.5 }} onPress={() => setSelectedNota(item)}>
                      <View style={{flexDirection: 'row', alignItems: 'center'}}><Text style={[styles.td, { fontWeight: 'bold', color: '#0F172A' }]} numberOfLines={1}>{item.nama_pelanggan}</Text><Ionicons name="receipt-outline" size={12} color="#0284C7" style={{marginLeft: 4}}/></View>
                      <Text style={{ fontSize: 10, color: '#64748B', marginTop: 2 }}>{formatTanggal(item.tanggal_pesanan)}</Text>
                    </TouchableOpacity>
                    <Text style={[styles.td, { flex: 1.2, fontWeight: 'bold', color: '#0F172A' }]}>Rp {item.total_harga}</Text>
                    <View style={{ flex: 1, alignItems: 'center' }}><Text style={[styles.badgeStatus, { backgroundColor: item.status.toLowerCase() === 'selesai' ? '#10B981' : '#F59E0B' }]}>{item.status.toUpperCase()}</Text></View>
                  </View>
                ))}
              </View>
            </>
          )}
        </ScrollView>
      </View>

      <Modal visible={!!selectedNota} transparent={true} animationType="fade">
        <View style={styles.modalOverlay}>
          {selectedNota && (
            <View style={styles.notaCard}>
              <ScrollView showsVerticalScrollIndicator={false}>
                <Text style={styles.notaHeader}>KANTIN PERKAPALAN</Text>
                <Text style={styles.notaSubHeader}>Resi: #{selectedNota.id} | Kasir: Admin</Text>
                <Text style={styles.notaSubHeader}>{formatTanggal(selectedNota.tanggal_pesanan)}</Text>
                <Text style={styles.notaSubHeader}>Pembeli: <Text style={{fontWeight:'bold'}}>{selectedNota.nama_pelanggan}</Text></Text>
                <Text style={styles.dashedLine}>------------------------------------------------</Text>
                {selectedNota.details && selectedNota.details.map((det, i) => (
                  <View key={i} style={styles.notaItemRow}>
                    <View><Text style={styles.notaText}>{det.nama_jajanan.toUpperCase()}</Text><Text style={styles.notaText}>{det.jumlah} x {det.harga}</Text></View>
                    <Text style={styles.notaText}>{det.subtotal}</Text>
                  </View>
                ))}
                <Text style={styles.dashedLine}>------------------------------------------------</Text>
                <View style={styles.notaTotalRow}><Text style={styles.notaTextBold}>TOTAL:</Text><Text style={styles.notaTextBold}>RP {selectedNota.total_harga}</Text></View>
                <View style={styles.notaTotalRow}><Text style={styles.notaText}>BAYAR ({selectedNota.metode_pembayaran.toUpperCase()}):</Text><Text style={styles.notaText}>{selectedNota.uang_pembeli}</Text></View>
                <View style={styles.notaTotalRow}><Text style={styles.notaText}>KEMBALI:</Text><Text style={styles.notaText}>{selectedNota.kembalian}</Text></View>
                <Text style={styles.dashedLine}>------------------------------------------------</Text>
                <Text style={styles.notaFooter}>Terima Kasih Telah Membeli</Text><Text style={styles.notaFooter}>Di Kantin Perkapalan</Text>
              </ScrollView>
              <TouchableOpacity style={styles.btnTutupNota} onPress={() => setSelectedNota(null)}><Text style={styles.btnTutupText}>Tutup Nota</Text></TouchableOpacity>
            </View>
          )}
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#0F172A', paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0 },
  globalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: '#0F172A', paddingHorizontal: 15, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#1E293B' },
  headerLeft: { flexDirection: 'row', alignItems: 'center' },
  headerLogo: { color: '#FFFFFF', fontSize: 13, fontWeight: '900', marginLeft: 6, letterSpacing: 0.5 },
  headerRight: { flexDirection: 'row', alignItems: 'center' },
  userInfo: { flexDirection: 'row', alignItems: 'center', marginRight: 10 },
  userName: { color: '#FFFFFF', fontSize: 10, fontWeight: 'bold', marginLeft: 4 },
  userRole: { color: '#FACC15' },
  btnLogoutGlobal: { borderWidth: 1, borderColor: '#EF4444', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 4 },
  btnLogoutGlobalText: { color: '#EF4444', fontSize: 9, fontWeight: 'bold' },

  mainContainer: { flex: 1, backgroundColor: '#F1F5F9' },
  scrollContent: { padding: 15, paddingTop: 20, paddingBottom: 100 },
  cardContainer: { marginBottom: 20 },
  financeCard: { padding: 20, borderRadius: 12, marginBottom: 15, elevation: 3, position: 'relative', overflow: 'hidden' },
  cardHeaderRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
  financeLabel: { color: 'rgba(255,255,255,0.9)', fontSize: 13, fontWeight: '600' },
  financeValue: { color: '#FFFFFF', fontSize: 28, fontWeight: 'bold' },
  cardIconBg: { position: 'absolute', right: -10, top: 15 },
  tableContainer: { backgroundColor: '#FFFFFF', borderRadius: 12, elevation: 2, overflow: 'hidden' },
  tableHeaderSection: { backgroundColor: '#0F172A', padding: 15, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  sectionTitle: { color: '#FFFFFF', fontSize: 18, fontWeight: 'bold' },
  sectionSubtitle: { color: '#94A3B8', fontSize: 11, marginTop: 4 },
  btnReset: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#EF4444', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 6 },
  btnResetText: { color: 'white', fontWeight: 'bold', fontSize: 12, marginLeft: 4 },
  tableHeader: { flexDirection: 'row', backgroundColor: '#F8FAFC', paddingVertical: 12, paddingHorizontal: 15, borderBottomWidth: 1, borderBottomColor: '#E2E8F0' },
  th: { fontSize: 12, fontWeight: 'bold', color: '#334155' },
  tableRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, paddingHorizontal: 15, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  td: { fontSize: 13, color: '#334155' },
  badgeStatus: { color: '#FFFFFF', fontSize: 10, fontWeight: 'bold', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6, textAlign: 'center' },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'center', alignItems: 'center', padding: 20 },
  notaCard: { backgroundColor: '#FFFFFF', width: '100%', maxHeight: '80%', padding: 25, borderRadius: 12, elevation: 10 },
  notaHeader: { fontSize: 20, fontWeight: '900', textAlign: 'center', fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace', color: '#000' },
  notaSubHeader: { fontSize: 12, textAlign: 'center', fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace', color: '#000', marginTop: 4 },
  dashedLine: { textAlign: 'center', color: '#000', marginVertical: 10, letterSpacing: -1 },
  notaItemRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
  notaText: { fontSize: 13, fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace', color: '#000' },
  notaTextBold: { fontSize: 14, fontWeight: 'bold', fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace', color: '#000' },
  notaTotalRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 5 },
  notaFooter: { fontSize: 12, textAlign: 'center', fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace', color: '#000', marginTop: 2 },
  btnTutupNota: { backgroundColor: '#0F172A', paddingVertical: 12, borderRadius: 30, alignItems: 'center', marginTop: 20 },
  btnTutupText: { color: '#FFFFFF', fontWeight: 'bold', fontSize: 14 }
});