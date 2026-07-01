import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Alert, SafeAreaView, StatusBar, ScrollView, Platform, TextInput, KeyboardAvoidingView } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export default function KasirScreen({ navigation }) {
  const [menuList, setMenuList] = useState([]);
  const [namaPembeli, setNamaPembeli] = useState('');
  const [nominalUang, setNominalUang] = useState('');
  const [metodePembayaran, setMetodePembayaran] = useState('Cash'); // Tambahan State Metode Pembayaran
  
  // URL SUDAH DISESUAIKAN DENGAN IP TERAKHIR ANDA
  // Untuk file lainnya (Dashboard, Kasir, Admin, Logbook):
  const BASE_URL = "http://10.234.56.211:3000/api";

  useEffect(() => { fetchMenu(); }, []);

  const fetchMenu = async () => {
    try {
      const response = await fetch(`${BASE_URL}/menu`);
      const result = await response.json();
      if (result.success) {
        const mappedData = result.data.map(item => ({ ...item, selected: false, qty: 1 }));
        setMenuList(mappedData);
      }
    } catch (error) { console.log(error); }
  };

  const toggleSelect = (id) => setMenuList(menuList.map(item => item.id === id ? { ...item, selected: !item.selected } : item));
  const updateQty = (id, delta) => setMenuList(menuList.map(item => { if (item.id === id) { const newQty = item.qty + delta; if (newQty < 1) return { ...item, qty: 1 }; if (newQty > item.stok) return { ...item, qty: parseInt(item.stok) }; return { ...item, qty: newQty }; } return item; }));
  const addQtyQuick = (id, amount) => setMenuList(menuList.map(item => { if (item.id === id) { const newQty = item.qty + amount; return { ...item, qty: newQty > item.stok ? parseInt(item.stok) : newQty }; } return item; }));

  const getKeranjang = () => menuList.filter(item => item.selected);
  const totalTagihan = getKeranjang().reduce((sum, item) => sum + (item.harga * item.qty), 0);
  const uangPembeliNum = parseInt(nominalUang.replace(/[^0-9]/g, '')) || 0;
  
  // Logika Kembalian: Jika QRIS, kembalian selalu 0. Jika Cash, hitung selisihnya.
  const kembalian = metodePembayaran === 'Cash' ? (uangPembeliNum - totalTagihan) : 0;

  const addUang = (amount) => setNominalUang((uangPembeliNum + amount).toString());
  const clearUang = () => setNominalUang('');

  const prosesBayar = async () => {
    if (!namaPembeli.trim()) return Alert.alert("Peringatan", "Nama Pembeli wajib diisi!");
    const keranjang = getKeranjang();
    if (keranjang.length === 0) return Alert.alert("Kosong", "Pilih minimal 1 menu jajanan!");
    if (metodePembayaran === 'Cash' && kembalian < 0) return Alert.alert("Uang Kurang", "Nominal uang pembeli tidak cukup!");

    // Jika non-tunai, anggap uang_pembeli sama persis dengan total tagihan
    const finalUangPembeli = metodePembayaran === 'Cash' ? uangPembeliNum : totalTagihan;

    const dataPesanan = { 
        nama_pembeli: namaPembeli, 
        total_harga: totalTagihan, 
        uang_pembeli: finalUangPembeli, 
        kembalian: kembalian, 
        metode_pembayaran: metodePembayaran, // Menyimpan metode yang dipilih
        keranjang: keranjang.map(item => ({ id: item.id, qty: item.qty, subtotal: item.harga * item.qty })) 
    };

    try {
      const response = await fetch(`${BASE_URL}/checkout`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dataPesanan) });
      const result = await response.json();
      if (result.success) { Alert.alert("Sukses", "Pesanan berhasil dibuat!"); setNamaPembeli(''); setNominalUang(''); setMetodePembayaran('Cash'); fetchMenu(); } 
      else { Alert.alert("Gagal", "Kesalahan saat menyimpan pesanan."); }
    } catch (error) { Alert.alert("Error", "Gagal menghubungi server."); }
  };

  const handleLogout = () => {
    Alert.alert("Logout", "Yakin ingin berlabuh (keluar)?", [{ text: "Batal", style: "cancel" }, { text: "Logout", style: "destructive", onPress: () => navigation.replace('Login') }]);
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

      <KeyboardAvoidingView style={styles.mainContainer} behavior={Platform.OS === 'ios' ? 'padding' : null}>
        <View style={styles.pageHeader}>
          <Text style={styles.pageHeaderTitle}>🛒 Form Pesanan Baru</Text>
        </View>

        <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Nama Pembeli</Text>
            <View style={styles.inputContainer}>
              <Ionicons name="person" size={20} color="#64748B" style={styles.inputIcon} />
              <TextInput style={styles.textInput} placeholder="Contoh: Faisal TKRO 2..." value={namaPembeli} onChangeText={setNamaPembeli} />
            </View>
          </View>

          <View style={styles.section}>
            <Text style={styles.sectionTitle}>㗊 Pilih Menu</Text>
            <View style={styles.menuGrid}>
              {menuList.map((item) => (
                <View key={item.id} style={[styles.menuCard, item.selected && styles.menuCardSelected]}>
                  <TouchableOpacity style={styles.cardHeader} onPress={() => toggleSelect(item.id)}>
                    <Ionicons name={item.selected ? "checkbox" : "square-outline"} size={24} color={item.selected ? "#0284C7" : "#CBD5E1"} />
                    <Text style={styles.menuName} numberOfLines={1}>{item.nama_jajanan}</Text>
                  </TouchableOpacity>
                  <View style={styles.priceRow}>
                    <Text style={styles.badgePrice}>Rp {item.harga}</Text>
                    <Text style={styles.textStok}>Stok: {item.stok}</Text>
                  </View>
                  <View style={styles.qtyContainer}>
                    <TouchableOpacity style={styles.btnQty} onPress={() => updateQty(item.id, -1)}><Text style={styles.btnQtyText}>-</Text></TouchableOpacity>
                    <Text style={styles.qtyValue}>{item.qty}</Text>
                    <TouchableOpacity style={styles.btnQty} onPress={() => updateQty(item.id, 1)}><Text style={styles.btnQtyText}>+</Text></TouchableOpacity>
                  </View>
                  <View style={styles.quickQtyRow}>
                    <TouchableOpacity style={styles.btnQuickQty} onPress={() => addQtyQuick(item.id, 2)}><Text style={styles.quickQtyText}>+2</Text></TouchableOpacity>
                    <TouchableOpacity style={styles.btnQuickQty} onPress={() => addQtyQuick(item.id, 5)}><Text style={styles.quickQtyText}>+5</Text></TouchableOpacity>
                    <TouchableOpacity style={styles.btnQuickQty} onPress={() => addQtyQuick(item.id, 10)}><Text style={styles.quickQtyText}>+10</Text></TouchableOpacity>
                  </View>
                </View>
              ))}
            </View>
          </View>

          <View style={styles.paymentCard}>
            <Text style={styles.paymentTitle}>🧾 Rincian Pembayaran</Text>

            {/* SEKSI METODE PEMBAYARAN BARU */}
            <Text style={styles.paymentLabel}>Metode Pembayaran</Text>
            <View style={styles.methodRow}>
              <TouchableOpacity style={[styles.btnMethod, metodePembayaran === 'Cash' && styles.btnMethodActive]} onPress={() => setMetodePembayaran('Cash')}>
                <Ionicons name="cash-outline" size={18} color={metodePembayaran === 'Cash' ? '#FFFFFF' : '#64748B'} />
                <Text style={[styles.btnMethodText, metodePembayaran === 'Cash' && styles.textWhite]}>Cash</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.btnMethod, metodePembayaran === 'QRIS' && styles.btnMethodActive]} onPress={() => setMetodePembayaran('QRIS')}>
                <Ionicons name="qr-code-outline" size={18} color={metodePembayaran === 'QRIS' ? '#FFFFFF' : '#64748B'} />
                <Text style={[styles.btnMethodText, metodePembayaran === 'QRIS' && styles.textWhite]}>QRIS / Transfer</Text>
              </TouchableOpacity>
            </View>

            <View style={[styles.paymentRow, {marginTop: 15}]}>
              <View style={{flex: 1}}><Text style={styles.paymentLabel}>Total Tagihan</Text><Text style={styles.totalTagihanText}>Rp {totalTagihan}</Text></View>
              <View style={{flex: 1, alignItems: 'flex-end'}}><Text style={styles.paymentLabel}>Kembalian</Text><Text style={[styles.kembalianText, { color: kembalian < 0 && metodePembayaran === 'Cash' ? '#EF4444' : '#10B981' }]}>Rp {kembalian < 0 ? 0 : kembalian}</Text></View>
            </View>
            
            {/* INPUT UANG HANYA MUNCUL JIKA METODE = CASH */}
            {metodePembayaran === 'Cash' && (
              <>
                <Text style={[styles.paymentLabel, {marginTop: 15}]}>Nominal Uang Pembeli (Cash)</Text>
                <View style={styles.inputUangContainer}>
                  <Text style={styles.rpPrefix}>Rp</Text>
                  <TextInput style={styles.inputUang} keyboardType="numeric" value={nominalUang} onChangeText={setNominalUang} placeholder="0" />
                </View>
                <View style={styles.quickMoneyGrid}>
                  {[500, 5000, 10000, 20000, 50000, 100000].map((val) => (
                    <TouchableOpacity key={val} style={styles.btnMoney} onPress={() => addUang(val)}><Text style={styles.btnMoneyText}>{val >= 1000 ? `${val/1000}k` : val}</Text></TouchableOpacity>
                  ))}
                  <TouchableOpacity style={styles.btnClearMoney} onPress={clearUang}><Text style={styles.btnClearText}>C</Text></TouchableOpacity>
                </View>
              </>
            )}

            <TouchableOpacity style={[styles.btnSubmit, {marginTop: metodePembayaran === 'QRIS' ? 20 : 0}]} onPress={prosesBayar}><Text style={styles.btnSubmitText}>Pesan & Bayar</Text></TouchableOpacity>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
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
  pageHeader: { padding: 20 },
  pageHeaderTitle: { fontSize: 22, fontWeight: 'bold', color: '#0F172A' },
  scrollContent: { paddingHorizontal: 15, paddingBottom: 100 },
  section: { marginBottom: 25 },
  sectionTitle: { fontSize: 16, fontWeight: 'bold', color: '#334155', marginBottom: 10, marginLeft: 5 },
  inputContainer: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#93C5FD', borderRadius: 8, paddingHorizontal: 10 },
  inputIcon: { marginRight: 10 },
  textInput: { flex: 1, paddingVertical: 12, fontSize: 15, color: '#0F172A' },
  menuGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  menuCard: { width: '48%', backgroundColor: '#FFFFFF', borderRadius: 10, padding: 12, marginBottom: 15, borderWidth: 1, borderColor: '#E2E8F0', elevation: 1 },
  menuCardSelected: { borderColor: '#38BDF8', borderWidth: 1.5 },
  cardHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: 10 },
  menuName: { fontSize: 14, fontWeight: 'bold', color: '#0F172A', marginLeft: 8, flex: 1 },
  priceRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  badgePrice: { backgroundColor: '#38BDF8', color: '#FFFFFF', fontSize: 11, fontWeight: 'bold', paddingHorizontal: 6, paddingVertical: 3, borderRadius: 4 },
  textStok: { fontSize: 11, color: '#64748B' },
  qtyContainer: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 6, marginBottom: 8 },
  btnQty: { padding: 8, paddingHorizontal: 12 },
  btnQtyText: { fontSize: 16, fontWeight: 'bold', color: '#64748B' },
  qtyValue: { fontSize: 16, fontWeight: 'bold', color: '#0F172A' },
  quickQtyRow: { flexDirection: 'row', justifyContent: 'space-between' },
  btnQuickQty: { flex: 1, alignItems: 'center', paddingVertical: 4, borderWidth: 1, borderColor: '#BAE6FD', borderRadius: 4, marginHorizontal: 2 },
  quickQtyText: { fontSize: 11, color: '#0284C7', fontWeight: 'bold' },
  paymentCard: { backgroundColor: '#FFFFFF', padding: 20, borderRadius: 12, borderWidth: 1, borderColor: '#E2E8F0', borderStyle: 'dashed', elevation: 2, marginBottom: 30 },
  paymentTitle: { fontSize: 16, fontWeight: 'bold', color: '#0F172A', marginBottom: 15, borderBottomWidth: 1, borderBottomColor: '#F1F5F9', paddingBottom: 10 },
  
  // STYLING BARU UNTUK METODE PEMBAYARAN
  methodRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 5 },
  btnMethod: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#F1F5F9', paddingVertical: 10, borderRadius: 8, marginHorizontal: 4, borderWidth: 1, borderColor: '#E2E8F0' },
  btnMethodActive: { backgroundColor: '#0284C7', borderColor: '#0369A1' },
  btnMethodText: { fontSize: 13, fontWeight: 'bold', color: '#64748B', marginLeft: 6 },
  textWhite: { color: '#FFFFFF' },

  paymentRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 10 },
  paymentLabel: { fontSize: 12, color: '#64748B', fontWeight: 'bold', marginBottom: 4 },
  totalTagihanText: { fontSize: 24, fontWeight: 'bold', color: '#EF4444' },
  kembalianText: { fontSize: 24, fontWeight: 'bold' },
  inputUangContainer: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: '#E2E8F0', borderRadius: 8, paddingHorizontal: 15, marginBottom: 15 },
  rpPrefix: { fontSize: 16, fontWeight: 'bold', color: '#64748B', marginRight: 10 },
  inputUang: { flex: 1, paddingVertical: 12, fontSize: 18, fontWeight: 'bold', color: '#0F172A' },
  quickMoneyGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', marginBottom: 20 },
  btnMoney: { width: '23%', backgroundColor: '#F8FAFC', paddingVertical: 8, alignItems: 'center', borderRadius: 6, borderWidth: 1, borderColor: '#E2E8F0', marginBottom: 8 },
  btnMoneyText: { fontSize: 13, color: '#0284C7', fontWeight: 'bold' },
  btnClearMoney: { width: '23%', backgroundColor: '#FEF2F2', paddingVertical: 8, alignItems: 'center', borderRadius: 6, borderWidth: 1, borderColor: '#FECACA', marginBottom: 8 },
  btnClearText: { fontSize: 13, color: '#EF4444', fontWeight: 'bold' },
  btnSubmit: { backgroundColor: '#0F172A', paddingVertical: 16, borderRadius: 8, alignItems: 'center' },
  btnSubmitText: { color: '#FFFFFF', fontSize: 16, fontWeight: 'bold' }
});