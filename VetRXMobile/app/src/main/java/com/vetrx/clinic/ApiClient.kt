// ApiClient.kt - Network layer for Veterinary Clinic Android App
package com.vetrx.clinic

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.asRequestBody
import retrofit2.Call
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.*
import java.io.File
import java.io.IOException
import java.util.concurrent.TimeUnit

object ApiClient {
    private const val BASE_URL = "https://app.vetrx.in/api/"
    
    private val okHttpClient = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(60, TimeUnit.SECONDS) // Longer timeout for file uploads
        .addInterceptor { chain ->
            val request = chain.request().newBuilder()
                .addHeader("Accept", "application/json")
                .addHeader("User-Agent", "VetRX-Android/1.0")
                .build()
            chain.proceed(request)
        }
        .build()
    
    private val retrofit = Retrofit.Builder()
        .baseUrl(BASE_URL)
        .client(okHttpClient)
        .addConverterFactory(GsonConverterFactory.create())
        .build()
    
    fun getService(): ApiService = retrofit.create(ApiService::class.java)
    
    // Separate upload function for multipart requests
    fun uploadFile(
        uid: String,
        type: String,
        note: String,
        forceNewVisit: Boolean,
        file: File,
        callback: (success: Boolean, response: UploadResponse?) -> Unit
    ) {
        val requestBody = MultipartBody.Builder()
            .setType(MultipartBody.FORM)
            .addFormDataPart("uid", uid)
            .addFormDataPart("type", type)
            .addFormDataPart("note", note)
            .addFormDataPart("forceNewVisit", forceNewVisit.toString())
            .addFormDataPart(
                "file", 
                file.name,
                file.asRequestBody("image/jpeg".toMediaType())
            )
            .build()
        
        val request = Request.Builder()
            .url("${BASE_URL}mobile/files")
            .post(requestBody)
            .build()
        
        okHttpClient.newCall(request).enqueue(object : Callback {
            override fun onFailure(call: okhttp3.Call, e: IOException) {
                callback(false, null)
            }
            
            override fun onResponse(call: okhttp3.Call, response: okhttp3.Response) {
                try {
                    if (response.isSuccessful) {
                        val responseBody = response.body?.string()
                        val uploadResponse = com.google.gson.Gson()
                            .fromJson(responseBody, UploadResponse::class.java)
                        callback(true, uploadResponse)
                    } else {
                        callback(false, null)
                    }
                } catch (e: Exception) {
                    callback(false, null)
                }
            }
        })
    }
}

interface ApiService {
    @POST("mobile/session")
    fun openVisit(@Body request: OpenVisitRequest): Call<OpenVisitResponse>
    
    @GET("mobile/today")
    fun getTodayVisits(@Query("uid") uid: String): Call<TodayVisitsResponse>
}

// AttachmentsAdapter.kt - RecyclerView adapter for displaying attachments
class AttachmentsAdapter : RecyclerView.Adapter<AttachmentsAdapter.ViewHolder>() {
    
    private var attachments = listOf<Attachment>()
    
    fun updateAttachments(newAttachments: List<Attachment>) {
        attachments = newAttachments
        notifyDataSetChanged()
    }
    
    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_attachment, parent, false)
        return ViewHolder(view)
    }
    
    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(attachments[position])
    }
    
    override fun getItemCount() = attachments.size
    
    class ViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val typeText: TextView = itemView.findViewById(R.id.typeText)
        private val filenameText: TextView = itemView.findViewById(R.id.filenameText)
        private val sizeText: TextView = itemView.findViewById(R.id.sizeText)
        private val noteText: TextView = itemView.findViewById(R.id.noteText)
        
        fun bind(attachment: Attachment) {
            typeText.text = attachment.type.uppercase()
            filenameText.text = attachment.filename
            sizeText.text = formatFileSize(attachment.size)
            noteText.text = attachment.note ?: "No notes"
            noteText.visibility = if (attachment.note.isNullOrBlank()) View.GONE else View.VISIBLE
        }
        
        private fun formatFileSize(bytes: Int): String {
            return when {
                bytes < 1024 -> "$bytes B"
                bytes < 1024 * 1024 -> "${bytes / 1024} KB"
                else -> "${bytes / (1024 * 1024)} MB"
            }
        }
    }
}
