// MainActivity.kt - Main entry point for Veterinary Clinic Android App
package com.vetrx.clinic

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Bundle
import android.provider.MediaStore
import android.widget.*
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.google.zxing.integration.android.IntentIntegrator
import com.google.zxing.integration.android.IntentResult
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import java.io.File
import java.text.SimpleDateFormat
import java.util.*

class MainActivity : AppCompatActivity() {
    
    private lateinit var uidEditText: EditText
    private lateinit var scanButton: Button
    private lateinit var openVisitButton: Button
    private lateinit var uploadButton: Button
    private lateinit var statusTextView: TextView
    private lateinit var petInfoLayout: LinearLayout
    private lateinit var petNameText: TextView
    private lateinit var ownerNameText: TextView
    private lateinit var attachmentsRecyclerView: RecyclerView
    private lateinit var attachmentsAdapter: AttachmentsAdapter
    
    private var currentVisit: Visit? = null
    private var currentPhotoUri: Uri? = null
    private val apiService = ApiClient.getService()
    
    private val cameraLauncher = registerForActivityResult(
        ActivityResultContracts.TakePicture()
    ) { success ->
        if (success) {
            currentPhotoUri?.let { uri ->
                showUploadDialog(uri)
            }
        }
    }
    
    private val galleryLauncher = registerForActivityResult(
        ActivityResultContracts.GetContent()
    ) { uri ->
        uri?.let { showUploadDialog(it) }
    }
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        
        initViews()
        setupClickListeners()
        requestPermissions()
    }
    
    private fun initViews() {
        uidEditText = findViewById(R.id.uidEditText)
        scanButton = findViewById(R.id.scanButton)
        openVisitButton = findViewById(R.id.openVisitButton)
        uploadButton = findViewById(R.id.uploadButton)
        statusTextView = findViewById(R.id.statusTextView)
        petInfoLayout = findViewById(R.id.petInfoLayout)
        petNameText = findViewById(R.id.petNameText)
        ownerNameText = findViewById(R.id.ownerNameText)
        attachmentsRecyclerView = findViewById(R.id.attachmentsRecyclerView)
        
        attachmentsAdapter = AttachmentsAdapter()
        attachmentsRecyclerView.layoutManager = LinearLayoutManager(this)
        attachmentsRecyclerView.adapter = attachmentsAdapter
    }
    
    private fun setupClickListeners() {
        scanButton.setOnClickListener { startQRScanner() }
        openVisitButton.setOnClickListener { openVisit() }
        uploadButton.setOnClickListener { showImageSourceDialog() }
    }
    
    private fun requestPermissions() {
        val permissions = arrayOf(
            Manifest.permission.CAMERA,
            Manifest.permission.READ_EXTERNAL_STORAGE,
            Manifest.permission.WRITE_EXTERNAL_STORAGE
        )
        
        permissions.forEach { permission ->
            if (ContextCompat.checkSelfPermission(this, permission) 
                != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(this, permissions, 100)
            }
        }
    }
    
    private fun startQRScanner() {
        val integrator = IntentIntegrator(this)
        integrator.setDesiredBarcodeFormats(IntentIntegrator.QR_CODE, IntentIntegrator.CODE_128)
        integrator.setPrompt("Scan patient QR code or barcode")
        integrator.setCameraId(0)
        integrator.setBeepEnabled(true)
        integrator.setBarcodeImageEnabled(true)
        integrator.initiateScan()
    }
    
   override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
    val result: IntentResult? = IntentIntegrator.parseActivityResult(requestCode, resultCode, data)
    if (result != null) {
        if (result.contents == null) {
            Toast.makeText(this, "Scan cancelled", Toast.LENGTH_SHORT).show()
        } else {
            val scannedUID = result.contents
            if (isValidUID(scannedUID)) {
                uidEditText.setText(scannedUID)
                updateStatus("Scanned UID: $scannedUID")
            } else {
                Toast.makeText(this, "Invalid UID format. Expected 6 digits.", Toast.LENGTH_SHORT).show()
            }
        }
    } else {
        // Handle non-QR scanner results (like gallery selection)
        super.onActivityResult(requestCode, resultCode, data)
    }
}
    
    private fun isValidUID(uid: String): Boolean {
        return uid.matches(Regex("^\\d{6}$"))
    }
    
    private fun openVisit() {
        val uid = uidEditText.text.toString().trim()
        if (!isValidUID(uid)) {
            Toast.makeText(this, "Please enter valid 6-digit UID", Toast.LENGTH_SHORT).show()
            return
        }
        
        updateStatus("Opening visit for $uid...")
        
        val request = OpenVisitRequest(uid)
        apiService.openVisit(request).enqueue(object : Callback<OpenVisitResponse> {
            override fun onResponse(call: Call<OpenVisitResponse>, response: Response<OpenVisitResponse>) {
                if (response.isSuccessful) {
                    response.body()?.let { openVisitResponse ->
                        if (openVisitResponse.ok) {
                            currentVisit = openVisitResponse.visit
                            displayPetInfo(openVisitResponse.pet, openVisitResponse.owner)
                            loadTodaysVisits(uid)
                            updateStatus("Visit opened successfully")
                        } else {
                            updateStatus("Failed to open visit")
                        }
                    }
                } else {
                    handleApiError(response.code())
                }
            }
            
            override fun onFailure(call: Call<OpenVisitResponse>, t: Throwable) {
                updateStatus("Network error: ${t.message}")
            }
        })
    }
    
    private fun loadTodaysVisits(uid: String) {
        apiService.getTodayVisits(uid).enqueue(object : Callback<TodayVisitsResponse> {
            override fun onResponse(call: Call<TodayVisitsResponse>, response: Response<TodayVisitsResponse>) {
                if (response.isSuccessful) {
                    response.body()?.let { todayResponse ->
                        if (todayResponse.ok && todayResponse.visits.isNotEmpty()) {
                            val attachments = todayResponse.visits[0].attachments
                            attachmentsAdapter.updateAttachments(attachments)
                        }
                    }
                }
            }
            
            override fun onFailure(call: Call<TodayVisitsResponse>, t: Throwable) {
                // Silently handle - not critical for main flow
            }
        })
    }
    
    private fun displayPetInfo(pet: Pet, owner: Owner) {
        petNameText.text = "Pet: ${pet.name} (${pet.species} - ${pet.breed})"
        ownerNameText.text = "Owner: ${owner.name} - ${owner.mobile}"
        petInfoLayout.visibility = LinearLayout.VISIBLE
        uploadButton.isEnabled = true
    }
    
    private fun showImageSourceDialog() {
        val options = arrayOf("Take Photo", "Choose from Gallery")
        AlertDialog.Builder(this)
            .setTitle("Select Image Source")
            .setItems(options) { _, which ->
                when (which) {
                    0 -> takePhoto()
                    1 -> chooseFromGallery()
                }
            }
            .show()
    }
    
    private fun takePhoto() {
        val photoFile = File(
            getExternalFilesDir("photos"),
            "photo_${System.currentTimeMillis()}.jpg"
        )
        photoFile.parentFile?.mkdirs()
        
        currentPhotoUri = FileProvider.getUriForFile(
            this,
            "${applicationContext.packageName}.fileprovider",
            photoFile
        )
        
        cameraLauncher.launch(currentPhotoUri)
    }
    
    private fun chooseFromGallery() {
        galleryLauncher.launch("image/*")
    }
    
    private fun showUploadDialog(imageUri: Uri) {
        val view = layoutInflater.inflate(R.layout.dialog_upload, null)
        val typeSpinner = view.findViewById<Spinner>(R.id.typeSpinner)
        val noteEditText = view.findViewById<EditText>(R.id.noteEditText)
        val forceNewVisitCheckBox = view.findViewById<CheckBox>(R.id.forceNewVisitCheckBox)
        
        // Setup document type spinner
        val types = arrayOf("prescription", "photo", "lab", "xray", "usg", "certificate", "report")
        typeSpinner.adapter = ArrayAdapter(this, android.R.layout.simple_spinner_dropdown_item, types)
        
        AlertDialog.Builder(this)
            .setTitle("Upload Document")
            .setView(view)
            .setPositiveButton("Upload") { _, _ ->
                val selectedType = typeSpinner.selectedItem.toString()
                val note = noteEditText.text.toString().trim()
                val forceNewVisit = forceNewVisitCheckBox.isChecked
                uploadFile(imageUri, selectedType, note, forceNewVisit)
            }
            .setNegativeButton("Cancel", null)
            .show()
    }
    
private fun uploadFile(imageUri: Uri, type: String, note: String, forceNewVisit: Boolean) {
    val uid = uidEditText.text.toString().trim()
    if (!isValidUID(uid)) {
        Toast.makeText(this, "Invalid UID", Toast.LENGTH_SHORT).show()
        return
    }
    
    updateStatus("Uploading $type...")
    
    try {
        val inputStream = contentResolver.openInputStream(imageUri)
        val file = File(cacheDir, "upload_${System.currentTimeMillis()}.jpg")
        file.outputStream().use { output ->
            inputStream?.copyTo(output)
        }
        
        updateStatus("File created: ${file.length()} bytes")
        
        ApiClient.uploadFile(
            uid = uid,
            type = type,
            note = note,
            forceNewVisit = forceNewVisit,
            file = file
        ) { success, response ->
            runOnUiThread {
                if (success && response != null) {
                    updateStatus("Upload successful: ${response.attachment.filename}")
                    loadTodaysVisits(uid)
                    Toast.makeText(this, "File uploaded successfully", Toast.LENGTH_SHORT).show()
                } else {
                    updateStatus("Upload failed - check network connection")
                    Toast.makeText(this, "Upload failed - check logs", Toast.LENGTH_LONG).show()
                }
            }
            file.delete()
        }
        
    } catch (e: Exception) {
        updateStatus("Upload error: ${e.message}")
        Toast.makeText(this, "Upload error: ${e.message}", Toast.LENGTH_LONG).show()
    }
}
    
    private fun handleApiError(code: Int) {
        val message = when (code) {
            404 -> "Pet not found. Please check UID."
            401 -> "Authentication failed"
            500 -> "Server error"
            else -> "HTTP Error: $code"
        }
        updateStatus(message)
        Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
    }
    
    private fun updateStatus(message: String) {
        val timestamp = SimpleDateFormat("HH:mm:ss", Locale.getDefault()).format(Date())
        statusTextView.text = "[$timestamp] $message"
    }
}

// Data classes are in DataClasses.kt
