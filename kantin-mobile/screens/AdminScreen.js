import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Alert, SafeAreaView, StatusBar, ScrollView, Platform, Modal, TextInput, KeyboardAvoidingView } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export default function AdminScreen({ navigation }) {
  const [menuList, setMenuList] = useState([]);
  const [modalVisible, setModalVisible] = useState(false);
  const [isEdit, setIsEdit] = useState(false);
  const [form, setForm] = useState({ id: '', nama_jajanan: '', harga_modal: '', harga: '', stok: '' });
  
  // URL SUDAH DISESUAIKAN DENGAN NODE.JS
  // Untuk file lainnya (Dashboard, Kasir, Admin, Logbook):
  const BASE_URL = "http://10.234.56.211:3000/api";

  useEffect(() => { fetchMenu(); }, []);

  const fetchMenu = async () => { try { const response = await fetch(`${BASE_URL}/menu`); const result = await response.json(); if (result.success) setMenuList(result.data); } catch (error) { console.log(error); } };

  const handleKosongkanStok = () => { Alert.alert("Peringatan", "Yakin ingin mengubah semua stok menjadi 0?", [{ text: "Batal", style: "cancel" }, { text: "Ya, Kosongkan", onPress: async () => { try { await fetch(`${BASE_URL}/kosongkan_stok`); fetchMenu(); Alert.alert("Sukses", "Semua stok berhasil dikosongkan."); } catch (e) { Alert.alert("Error", "Gagal mengosongkan stok."); } } }]); };
  
  const handleSimpanMenu = async () => { 
    if(!form.nama_jajanan || !form.harga || !form.stok) return Alert.alert("Error", "Nama, Harga, dan Stok wajib diisi!"); 
    const action = isEdit ? 'edit_menu' : 'tambah_menu'; 
    try { 
      const response = await fetch(`${BASE_URL}/${action}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(form) }); 
      const result = await response.json(); 
      if (result.success) { setModalVisible(false); fetchMenu(); Alert.alert("Sukses", isEdit ? "Menu berhasil diupdate!" : "Menu baru berhasil ditambahkan!"); } 
    } catch (e) { Alert.alert("Error", "Gagal menyimpan data."); } 
  };
  
  const handleHapusMenu = (id, nama) => { 
    Alert.alert("Hapus Menu", `Yakin ingin menghapus ${nama}?`, [{ text: "Batal", style: "cancel" }, { text: "Hapus", style: 'destructive', onPress: async () => { await fetch(`${BASE_URL}/hapus_menu`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) }); setModalVisible(false); fetchMenu(); } }]); 
  };

  const openAddModal = () => { setForm({ id: '', nama_jajanan: '', harga_modal: '', harga: '', stok: '' }); setIsEdit(false); setModalVisible(true); };
  const openEditModal = (item) => { setForm({ id: item.id, nama_jajanan: item.nama_jajanan, harga_modal: item.harga_modal ? item.harga_modal.toString() : '0', harga: item.harga.toString(), stok: item.stok.toString() }); setIsEdit(true); setModalVisible(true); };

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
        <View style={styles.pageHeader}>
          <View style={styles.titleRow}>
            <Ionicons name="cube-outline" size={24} color="#0F172A" />
            <Text style={styles.pageHeaderTitle}>Manajemen Stok</Text>
          </View>
          <View style={styles.headerButtons}>
            <TouchableOpacity style={styles.btnDanger} onPress={handleKosongkanStok}><Ionicons name="trash-outline" size={14} color="white" /><Text style={styles.btnDangerText}>Kosongkan Stok</Text></TouchableOpacity>
            <TouchableOpacity style={styles.btnWarning} onPress={openAddModal}><Ionicons name="add-circle-outline" size={14} color="#0F172A" /><Text style={styles.btnWarningText}>Tambah Menu</Text></TouchableOpacity>
          </View>
        </View>

        <ScrollView contentContainerStyle={styles.scrollContent}>
          <View style={styles.gridContainer}>
            {menuList.map((item) => (
              <View key={item.id} style={styles.card}>
                <View style={styles.cardHeader}>
                  <Text style={styles.cardTitle} numberOfLines={1}>{item.nama_jajanan}</Text>
                  <View style={[styles.badgeStok, { backgroundColor: item.stok <= 0 ? '#EF4444' : '#10B981' }]}><Ionicons name="cube-outline" size={12} color="white" /><Text style={styles.badgeStokText}>{item.stok}</Text></View>
                </View>
                <View style={styles.priceContainer}>
                  <View style={styles.priceRow}><Ionicons name="pricetag-outline" size={14} color="#94A3B8" /><Text style={styles.textModal}>Modal: Rp {item.harga_modal || 0}</Text></View>
                  <View style={styles.priceRow}><Ionicons name="pricetag" size={14} color="#059669" /><Text style={styles.textJual}>Jual: Rp {item.harga}</Text></View>
                </View>
                <TouchableOpacity style={styles.btnEditOutline} onPress={() => openEditModal(item)}><Ionicons name="create-outline" size={16} color="#0284C7" /><Text style={styles.btnEditText}>Edit Menu & Stok</Text></TouchableOpacity>
              </View>
            ))}
          </View>
        </ScrollView>
      </View>

      <Modal visible={modalVisible} animationType="slide" transparent={true}>
        <View style={styles.modalOverlay}>
          <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={styles.modalContent}>
            <View style={styles.modalHeader}><Text style={styles.modalTitle}>{isEdit ? 'Edit Menu' : 'Tambah Menu Baru'}</Text><TouchableOpacity onPress={() => setModalVisible(false)}><Ionicons name="close-circle" size={28} color="#EF4444" /></TouchableOpacity></View>
            <ScrollView showsVerticalScrollIndicator={false}>
              <Text style={styles.inputLabel}>Nama Menu</Text><TextInput style={styles.inputField} value={form.nama_jajanan} onChangeText={(val) => setForm({...form, nama_jajanan: val})} />
              <Text style={styles.inputLabel}>Harga Modal (Rp)</Text><TextInput style={styles.inputField} keyboardType="numeric" value={form.harga_modal} onChangeText={(val) => setForm({...form, harga_modal: val})} />
              <Text style={styles.inputLabel}>Harga Jual (Rp)</Text><TextInput style={styles.inputField} keyboardType="numeric" value={form.harga} onChangeText={(val) => setForm({...form, harga: val})} />
              <Text style={styles.inputLabel}>Jumlah Stok Baru</Text><TextInput style={styles.inputField} keyboardType="numeric" value={form.stok} onChangeText={(val) => setForm({...form, stok: val})} />
              <TouchableOpacity style={styles.btnSave} onPress={handleSimpanMenu}><Text style={styles.btnSaveText}>Simpan Perubahan</Text></TouchableOpacity>
              {isEdit && <TouchableOpacity style={styles.btnDeleteFull} onPress={() => handleHapusMenu(form.id, form.nama_jajanan)}><Text style={styles.btnDeleteText}>Hapus Menu Ini</Text></TouchableOpacity>}
            </ScrollView>
          </KeyboardAvoidingView>
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
  pageHeader: { backgroundColor: '#FFFFFF', padding: 20, borderBottomWidth: 1, borderBottomColor: '#E2E8F0', elevation: 2 },
  titleRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 15 },
  pageHeaderTitle: { fontSize: 22, fontWeight: 'bold', color: '#0F172A', marginLeft: 10 },
  headerButtons: { flexDirection: 'row', justifyContent: 'space-between' },
  btnDanger: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#EF4444', paddingVertical: 10, paddingHorizontal: 10, borderRadius: 6, flex: 0.48, justifyContent: 'center' },
  btnDangerText: { color: 'white', fontWeight: 'bold', marginLeft: 5, fontSize: 12 },
  btnWarning: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FACC15', paddingVertical: 10, paddingHorizontal: 10, borderRadius: 6, flex: 0.48, justifyContent: 'center' },
  btnWarningText: { color: '#0F172A', fontWeight: 'bold', marginLeft: 5, fontSize: 12 },
  scrollContent: { padding: 15, paddingBottom: 100 },
  gridContainer: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  card: { width: '48%', backgroundColor: '#FFFFFF', borderRadius: 8, padding: 12, marginBottom: 15, borderWidth: 1, borderColor: '#E2E8F0', elevation: 1 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 15 },
  cardTitle: { fontSize: 14, fontWeight: 'bold', color: '#0F172A', flex: 1, paddingRight: 5 },
  badgeStok: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 6, paddingVertical: 3, borderRadius: 4 },
  badgeStokText: { color: 'white', fontSize: 11, fontWeight: 'bold', marginLeft: 4 },
  priceContainer: { marginBottom: 15, borderTopWidth: 1, borderBottomWidth: 1, borderColor: '#F1F5F9', paddingVertical: 10 },
  priceRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 4 },
  textModal: { color: '#94A3B8', fontSize: 12, marginLeft: 6 },
  textJual: { color: '#059669', fontSize: 13, fontWeight: 'bold', marginLeft: 6 },
  btnEditOutline: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', borderWidth: 1, borderColor: '#BAE6FD', paddingVertical: 8, borderRadius: 6 },
  btnEditText: { color: '#0284C7', fontWeight: 'bold', fontSize: 11, marginLeft: 5 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(15, 23, 42, 0.6)', justifyContent: 'flex-end' },
  modalContent: { backgroundColor: '#FFFFFF', borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: 25, maxHeight: '85%' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20, borderBottomWidth: 1, borderBottomColor: '#F1F5F9', paddingBottom: 15 },
  modalTitle: { fontSize: 20, fontWeight: 'bold', color: '#0F172A' },
  inputLabel: { fontSize: 14, fontWeight: 'bold', color: '#334155', marginBottom: 5 },
  inputField: { borderWidth: 1, borderColor: '#CBD5E1', borderRadius: 8, padding: 12, marginBottom: 15, fontSize: 15, color: '#0F172A', backgroundColor: '#F8FAFC' },
  btnSave: { backgroundColor: '#0284C7', padding: 15, borderRadius: 8, alignItems: 'center', marginTop: 10 },
  btnSaveText: { color: 'white', fontWeight: 'bold', fontSize: 16 },
  btnDeleteFull: { padding: 15, alignItems: 'center', marginTop: 10 },
  btnDeleteText: { color: '#EF4444', fontWeight: 'bold', fontSize: 15 }
});