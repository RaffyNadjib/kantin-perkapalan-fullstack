import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, StatusBar, Platform, ScrollView, ActivityIndicator, TouchableOpacity, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export default function DashboardScreen({ navigation }) {
  const [data, setData] = useState({ total_stok: 0, pending: 0, menu: [], antrean: [] });
  const [loading, setLoading] = useState(true);
  const [expanded, setExpanded] = useState(null);
  const [checkedItems, setCheckedItems] = useState([]); 

  // URL SUDAH MENGGUNAKAN IP YANG BENAR
  // Untuk file lainnya (Dashboard, Kasir, Admin, Logbook):
  const BASE_URL = "http://10.234.56.211:3000/api";

  useEffect(() => { fetchDashboard(); }, []);

  const fetchDashboard = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${BASE_URL}/dashboard`);
      const result = await response.json();
      if (result.success) setData(result);
    } catch (error) { console.log(error); }
    finally { setLoading(false); }
  };

  const updateStatus = async (id, isAuto = false) => {
    try {
      const response = await fetch(`${BASE_URL}/update_status`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, status: 'selesai' })
      });
      const result = await response.json();
      
      if (result.success) {
        if (!isAuto) Alert.alert("Sukses", "Pesanan telah diselesaikan!");
        fetchDashboard(); 
      } else {
        Alert.alert("Gagal", "Server menolak mengubah status pesanan.");
      }
    } catch (error) { 
      Alert.alert("Koneksi Error", "Gagal menghubungi server untuk mengubah status."); 
    }
  };

  const toggleCheck = (detailId, pesananId) => {
    let updatedChecked;
    if (checkedItems.includes(detailId)) {
      updatedChecked = checkedItems.filter(id => id !== detailId);
    } else {
      updatedChecked = [...checkedItems, detailId];
    }
    setCheckedItems(updatedChecked);

    // Cek apakah semua pesanan di resi ini sudah dicentang
    const pesananSaatIni = data.antrean.find(item => item.id === pesananId);
    if (pesananSaatIni && pesananSaatIni.details) {
      const semuaDicentang = pesananSaatIni.details.every(d => updatedChecked.includes(d.id));
      
      if (semuaDicentang && pesananSaatIni.status.toLowerCase() !== 'selesai') {
        // Memberikan jeda 0.5 detik agar animasi coretan terlihat dulu
        setTimeout(() => {
          Alert.alert(
            "Pesanan Lengkap!", 
            `Semua menu untuk ${pesananSaatIni.nama_pelanggan} telah siap.\nStatus otomatis diselesaikan.`
          );
          updateStatus(pesananId, true);
        }, 500);
      }
    }
  };

  const handleLogout = () => {
    Alert.alert("Logout", "Yakin ingin berlabuh (keluar)?", [
      { text: "Batal", style: "cancel" },
      { text: "Logout", style: "destructive", onPress: () => navigation.replace('Login') }
    ]);
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar backgroundColor="#0F172A" barStyle="light-content" />
      
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
        <View style={styles.pageHeader}>
          <Ionicons name="compass-outline" size={24} color="#0F172A" />
          <Text style={styles.pageHeaderTitle}>Status Terkini</Text>
        </View>

        <ScrollView contentContainerStyle={styles.scrollContent}>
          {loading ? <ActivityIndicator size="large" color="#0284C7" style={{marginTop: 50}} /> : (
            <>
              <View style={styles.summaryRow}>
                <View style={[styles.summaryCard, { borderLeftColor: '#F59E0B' }]}><Text style={styles.summaryLabel}>Pending</Text><Text style={styles.summaryValue}>{data.pending}</Text></View>
                <View style={[styles.summaryCard, { borderLeftColor: '#0284C7' }]}><Text style={styles.summaryLabel}>Stok</Text><Text style={styles.summaryValue}>{data.total_stok}</Text></View>
              </View>

              <View style={styles.sectionContainer}>
                <Text style={styles.sectionTitle}>Antrean Transaksi</Text>
                <View style={styles.tableCard}>
                  <View style={styles.tableHeader}>
                    <Text style={[styles.th, { flex: 0.8 }]}>Resi</Text>
                    <Text style={[styles.th, { flex: 1.5 }]}>Pelanggan</Text>
                    <Text style={[styles.th, { flex: 1, textAlign: 'center' }]}>Status</Text>
                    <Text style={[styles.th, { flex: 0.8, textAlign: 'center' }]}>Aksi</Text>
                  </View>

                  {data.antrean && data.antrean.map((item) => (
                    <View key={item.id}>
                      <TouchableOpacity style={styles.tableRow} onPress={() => setExpanded(expanded === item.id ? null : item.id)}>
                        <Text style={[styles.td, { flex: 0.8 }]}>#{item.id}</Text>
                        <View style={{ flex: 1.5, flexDirection: 'row', alignItems: 'center' }}>
                          <Text style={[styles.td, { fontWeight: 'bold', marginRight: 5 }]}>{item.nama_pelanggan}</Text>
                          <Ionicons name={expanded === item.id ? "caret-up" : "caret-down"} size={14} color="#F59E0B" />
                        </View>
                        <View style={{ flex: 1, alignItems: 'center' }}>
                          <Text style={[styles.badgeStatus, { backgroundColor: item.status.toLowerCase() === 'selesai' ? '#0ea5e9' : '#F59E0B' }]}>{item.status.toUpperCase()}</Text>
                        </View>
                        <View style={{ flex: 0.8, alignItems: 'center' }}>
                          {item.status.toLowerCase() !== 'selesai' ? (
                            <TouchableOpacity style={styles.btnSelesai} onPress={() => updateStatus(item.id, false)}>
                              <Ionicons name="checkmark" size={16} color="white" />
                            </TouchableOpacity>
                          ) : <Ionicons name="checkmark-done" size={20} color="#94A3B8" />}
                        </View>
                      </TouchableOpacity>

                      {expanded === item.id && item.details && (
                        <View style={styles.dropdownContainer}>
                          {item.details.map((d) => {
                            const isChecked = checkedItems.includes(d.id);
                            return (
                              <View key={d.id} style={styles.detailRow}>
                                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                                  <Text style={[styles.qtyBadge, isChecked && { backgroundColor: '#CBD5E1' }]}>{d.jumlah}x</Text>
                                  {/* EFEK CORETAN TEKS DITAMBAHKAN DI SINI */}
                                  <Text style={[styles.detailText, isChecked && { textDecorationLine: 'line-through', color: '#94A3B8' }]}>
                                    {d.nama_jajanan}
                                  </Text>
                                </View>
                                <TouchableOpacity 
                                  style={[styles.btnCheckItem, isChecked && { backgroundColor: '#D1FAE5', borderColor: '#10B981' }]} 
                                  onPress={() => toggleCheck(d.id, item.id)}
                                >
                                  <Ionicons name="checkmark" size={16} color={isChecked ? "#10B981" : "transparent"} />
                                </TouchableOpacity>
                              </View>
                            );
                          })}
                        </View>
                      )}
                    </View>
                  ))}
                </View>
              </View>

              <View style={[styles.sectionContainer, { marginBottom: 60 }]}>
                <Text style={styles.sectionTitle}>Ketersediaan Menu</Text>
                <View style={styles.tableCard}>
                  <View style={styles.tableHeader}>
                    <Text style={[styles.th, { flex: 2 }]}>Nama Menu</Text>
                    <Text style={[styles.th, { flex: 1 }]}>Harga</Text>
                    <Text style={[styles.th, { flex: 0.8, textAlign: 'center' }]}>Sisa Stok</Text>
                  </View>
                  {data.menu && data.menu.map((menuItem, index) => (
                    <View key={index} style={styles.tableRow}>
                      <Text style={[styles.td, { flex: 2 }]}>{menuItem.nama_jajanan}</Text>
                      <Text style={[styles.td, { flex: 1, color: '#64748B' }]}>Rp {menuItem.harga}</Text>
                      <View style={{ flex: 0.8, alignItems: 'center' }}><Text style={styles.badgeStok}>{menuItem.stok}</Text></View>
                    </View>
                  ))}
                </View>
              </View>
            </>
          )}
        </ScrollView>
      </View>
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

  mainContainer: { flex: 1, backgroundColor: '#F8FAFC' },
  pageHeader: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingTop: 20, paddingBottom: 10 },
  pageHeaderTitle: { fontSize: 20, fontWeight: 'bold', color: '#0F172A', marginLeft: 10 },
  
  scrollContent: { paddingHorizontal: 20, paddingBottom: 50 },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between' },
  summaryCard: { flex: 1, backgroundColor: '#FFFFFF', padding: 15, borderRadius: 12, elevation: 2, borderLeftWidth: 4, marginHorizontal: 5 },
  summaryLabel: { fontSize: 12, color: '#64748B', fontWeight: 'bold', marginBottom: 5 },
  summaryValue: { fontSize: 24, fontWeight: 'bold' },
  sectionContainer: { marginTop: 20 },
  sectionTitle: { fontSize: 16, fontWeight: 'bold', marginBottom: 10 },
  tableCard: { backgroundColor: '#FFFFFF', borderRadius: 12, elevation: 2, overflow: 'hidden' },
  tableHeader: { flexDirection: 'row', backgroundColor: '#F1F5F9', paddingVertical: 12, paddingHorizontal: 15 },
  th: { fontSize: 12, fontWeight: 'bold', color: '#334155' },
  tableRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, paddingHorizontal: 15, borderBottomWidth: 1, borderBottomColor: '#F8FAFC' },
  td: { fontSize: 13, color: '#0F172A' },
  badgeStatus: { color: '#FFFFFF', fontSize: 10, fontWeight: 'bold', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6 },
  badgeStok: { backgroundColor: '#059669', color: '#FFFFFF', fontSize: 12, fontWeight: 'bold', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  btnSelesai: { backgroundColor: '#10B981', padding: 8, borderRadius: 6, elevation: 1 },
  dropdownContainer: { backgroundColor: '#FFFFFF', padding: 15, borderBottomWidth: 1, borderBottomColor: '#E2E8F0', marginHorizontal: 10, borderRadius: 8, marginBottom: 10, elevation: 1 },
  detailRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  qtyBadge: { backgroundColor: '#64748B', color: '#FFFFFF', fontSize: 11, fontWeight: 'bold', paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4, marginRight: 10 },
  detailText: { fontSize: 13, color: '#334155', fontWeight: '500' },
  btnCheckItem: { width: 24, height: 24, borderRadius: 4, borderWidth: 1, borderColor: '#CBD5E1', justifyContent: 'center', alignItems: 'center' }
});